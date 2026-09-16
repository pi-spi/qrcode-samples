<?php

declare(strict_types=1);

namespace PiSpi\Sample;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Catidegla\PiSpiQr\Exceptions\MalformedPayload;
use Catidegla\PiSpiQr\MerchantChannel;
use Catidegla\PiSpiQr\Payload;
use Catidegla\PiSpiQr\QrCode;

/**
 * Les deux operations de la demo, isolees du transport HTTP.
 *
 * Le SDK construit et relit le payload. Il ne dessine pas l'image, parce que
 * dessiner un QR est un probleme deja resolu en PHP alors que le profil PI-SPI
 * ne l'est pas. Le rendu passe donc par bacon/bacon-qr-code, que vous pouvez
 * remplacer par la bibliotheque QR que votre projet utilise deja.
 */
final class Qr
{
    public const COUNTRIES = [
        'BJ' => 'Benin',
        'BF' => 'Burkina Faso',
        'CI' => 'Cote d Ivoire',
        'GW' => 'Guinee-Bissau',
        'ML' => 'Mali',
        'NE' => 'Niger',
        'SN' => 'Senegal',
        'TG' => 'Togo',
    ];

    public const TYPES = [
        'STATIC' => 'Statique',
        'DYNAMIC' => 'Dynamique',
    ];

    /**
     * @param  array<string, mixed>  $input
     * @return array{payload: string, decoded: array<string, string>, svg: string}
     *
     * @throws InvalidInput
     */
    public function generate(array $input): array
    {
        $type = (string) ($input['qr_type'] ?? 'DYNAMIC');
        $alias = trim((string) ($input['merchant_id'] ?? ''));
        $country = (string) ($input['country'] ?? 'SN');
        $reference = trim((string) ($input['reference'] ?? ''));

        if ($alias === '') {
            throw new InvalidInput('L identifiant marchand est requis.', 400);
        }

        if (! isset(self::TYPES[$type])) {
            throw new InvalidInput('Type de QR invalide : ' . $type, 400);
        }

        if (! isset(self::COUNTRIES[$country])) {
            throw new InvalidInput('Code pays invalide : ' . $country, 400);
        }

        $amount = $this->amount($input['amount'] ?? '');

        // Encodage. Le constructeur choisi porte le canal marchand : un QR
        // dynamique exige un identifiant de transaction, un QR statique n en
        // accepte pas. C est le SDK qui tient cette regle, pas l appelant.
        try {
            if ($type === 'DYNAMIC') {
                if ($amount === null) {
                    throw new InvalidInput('Un QR dynamique porte un montant.', 400);
                }

                if ($reference === '') {
                    throw new InvalidInput('Un QR dynamique porte un identifiant de transaction.', 400);
                }

                $qr = QrCode::dynamic($alias, $country, $amount, $reference);
            } elseif ($amount !== null) {
                $qr = QrCode::staticWithAmount($alias, $country, $amount);
            } else {
                $qr = QrCode::static($alias, $country);
            }

            $payload = $qr->toPayload();
        } catch (InvalidInput $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new InvalidInput('Erreur d encodage : ' . $e->getMessage(), 422, 'encode');
        }

        // Relecture de verification. Le SDK expose problems(), qui rend la
        // liste complete plutot que de s arreter a la premiere faute, donc un
        // payload refuse dit tout ce qui ne va pas en une seule fois.
        try {
            $decoded = Payload::parse($payload);
        } catch (MalformedPayload $e) {
            throw new InvalidInput('Erreur de decodage : ' . $e->getMessage(), 422, 'decode');
        }

        $problems = $decoded->problems();

        if ($problems !== []) {
            throw new InvalidInput('Erreur de decodage : ' . implode(' ; ', $problems), 422, 'decode');
        }

        try {
            $svg = $this->svg($payload);
        } catch (\Throwable $e) {
            throw new InvalidInput('Erreur de generation SVG : ' . $e->getMessage(), 422, 'svg');
        }

        return [
            'payload' => $payload,
            'decoded' => $this->describe($decoded),
            'svg' => $svg,
        ];
    }

    /**
     * @return array{decoded: array<string, string>}
     *
     * @throws InvalidInput
     */
    public function decode(string $payload): array
    {
        $payload = trim($payload);

        if ($payload === '') {
            throw new InvalidInput('Le payload est requis.', 400);
        }

        try {
            $parsed = Payload::parse($payload);
        } catch (MalformedPayload $e) {
            throw new InvalidInput('Payload invalide : ' . $e->getMessage(), 422, 'decode');
        }

        $problems = $parsed->problems();

        if ($problems !== []) {
            throw new InvalidInput('Payload invalide : ' . implode(' ; ', $problems), 422, 'decode');
        }

        return ['decoded' => $this->describe($parsed)];
    }

    /**
     * Le montant est un entier de francs. Le XOF n a pas de sous-unite, donc
     * 1500 vaut mille cinq cents francs et non quinze. Un montant fractionnaire
     * est refuse ici plutot que silencieusement arrondi.
     *
     * @throws InvalidInput
     */
    private function amount(mixed $raw): ?int
    {
        if (is_int($raw)) {
            return $raw;
        }

        if ($raw === null || $raw === false) {
            return null;
        }

        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^[0-9]+$/', $raw) !== 1) {
            throw new InvalidInput(
                'Montant invalide : ' . $raw . '. Le XOF n a pas de sous-unite, '
                . 'le montant est un nombre entier de francs.',
                400
            );
        }

        return (int) $raw;
    }

    /**
     * @return array<string, string>
     */
    private function describe(Payload $payload): array
    {
        $country = $payload->country();
        $currency = $payload->currency();
        $channel = $payload->channel();

        $rows = [
            'Schema' => $payload->gui(),
            'Alias' => $payload->alias(),
            'Pays' => $country !== null
                ? $country . ' (' . (self::COUNTRIES[$country] ?? 'hors union') . ')'
                : null,
            'Devise' => $currency !== null
                ? $currency . ' (XOF, sans sous-unite)'
                : null,
            'Montant' => $payload->hasAmount()
                ? $payload->amount() . ' F'
                : 'saisi par le payeur',
            'Canal marchand' => $channel !== null
                ? $channel . ' (' . MerchantChannel::describe($channel) . ')'
                : null,
            'Identifiant de transaction' => $payload->transactionId(),
            'Nom du marchand' => $payload->merchantName(),
            'Ville du marchand' => $payload->merchantCity(),
            'Categorie marchand' => $payload->merchantCategory(),
            'Checksum' => $payload->checksum(),
        ];

        return array_filter(
            $rows,
            static fn (?string $value): bool => $value !== null && $value !== ''
        );
    }

    private function svg(string $payload): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle(330, 2),
            new SvgImageBackEnd()
        ));

        // Le payload PI-SPI est en ASCII, donc l encodage octet par defaut
        // suffit. Niveau de correction M, le meme compromis que les autres
        // demos : assez de redondance pour un ecran de caisse raye, sans
        // gonfler la matrice au point de gener la lecture.
        return $writer->writeString(
            $payload,
            Encoder::DEFAULT_BYTE_MODE_ENCODING,
            ErrorCorrectionLevel::M()
        );
    }
}
