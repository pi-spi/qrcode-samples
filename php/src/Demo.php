<?php

declare(strict_types=1);

namespace PiSpi\Demo;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Catidegla\PiSpiQr\Exceptions\MalformedPayload;
use Catidegla\PiSpiQr\Payload;
use Catidegla\PiSpiQr\QrCode;
use Catidegla\PiSpiQr\Spec;

/**
 * Génération et décodage de QR Codes PI-SPI.
 *
 * Deux bibliothèques, deux responsabilités. catidegla/pi-spi-qr construit et
 * relit le payload EMV, bacon/bacon-qr-code dessine l'image. Le profil PI-SPI
 * est la partie spécifique, le dessin d'un QR ne l'est pas.
 */
final class Demo
{
    /** Les huit États membres, dans l'ordre des exemples officiels. */
    public const COUNTRIES = [
        'BJ' => 'Bénin',
        'BF' => 'Burkina Faso',
        'CI' => "Côte d'Ivoire",
        'GW' => 'Guinée-Bissau',
        'ML' => 'Mali',
        'NE' => 'Niger',
        'SN' => 'Sénégal',
        'TG' => 'Togo',
    ];

    public const TYPES = [
        'STATIC' => 'Statique',
        'DYNAMIC' => 'Dynamique',
        'TRANSFER' => 'Transfert',
    ];

    /**
     * @param array<string, mixed> $input
     * @return array{status: int, body: array<string, mixed>}
     */
    public function generate(array $input): array
    {
        $type = strtoupper(trim((string) ($input['qr_type'] ?? 'DYNAMIC')));
        $alias = trim((string) ($input['alias'] ?? ''));
        $country = strtoupper(trim((string) ($input['country'] ?? 'SN')));
        $reference = trim((string) ($input['reference'] ?? ''));
        $rawAmount = trim((string) ($input['amount'] ?? ''));

        if (! isset(self::TYPES[$type])) {
            return self::fail(sprintf('Type de QR invalide : %s', $type));
        }

        if ($alias === '') {
            return self::fail("L'alias du bénéficiaire est requis.");
        }

        if (! isset(self::COUNTRIES[$country])) {
            return self::fail(sprintf('Code pays invalide : %s', $country));
        }

        // Le XOF n'a pas de sous-unité, donc un montant est un entier de francs.
        // Refuser "1500,50" ici plutôt que de l'arrondir en silence évite le
        // genre d'écart qui ne se voit qu'au rapprochement.
        $amount = null;
        if ($rawAmount !== '') {
            if (preg_match('/^\d+$/', $rawAmount) !== 1) {
                return self::fail(
                    'Le montant doit être un nombre entier de francs. '
                    .'Le XOF ne comporte pas de sous-unité, il n\'y a donc pas de centimes à saisir.'
                );
            }

            $amount = (int) $rawAmount;
        }

        try {
            $qr = $this->build($type, $alias, $country, $amount, $reference);
            $payload = $qr->toPayload();
        } catch (MalformedPayload $e) {
            return self::fail($e->getMessage(), 422);
        }

        // On relit ce qu'on vient d'écrire. Un payload qui ne se relit pas est
        // un payload qu'un téléphone refusera à la caisse, et il vaut mieux
        // l'apprendre ici.
        $decoded = Payload::parse($payload);

        if (! $decoded->isValid()) {
            return self::fail(
                'Le payload généré ne se relit pas : '.implode(' ', $decoded->problems()),
                500
            );
        }

        return [
            'status' => 200,
            'body' => [
                'payload' => $payload,
                'decoded' => self::describe($decoded),
                'svg' => $this->svg($payload),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{status: int, body: array<string, mixed>}
     */
    public function decode(array $input): array
    {
        $payload = trim((string) ($input['payload'] ?? ''));

        if ($payload === '') {
            return self::fail('Le payload est requis.');
        }

        try {
            $parsed = Payload::parse($payload);
        } catch (MalformedPayload $e) {
            return self::fail($e->getMessage(), 422);
        }

        // problems() est renvoyé même quand le payload est valide, parce qu'un
        // décodeur qui répond seulement "invalide" n'aide personne à réparer
        // quoi que ce soit.
        return [
            'status' => 200,
            'body' => [
                'valid' => $parsed->isValid(),
                'problems' => $parsed->problems(),
                'decoded' => self::describe($parsed),
            ],
        ];
    }

    private function build(
        string $type,
        string $alias,
        string $country,
        ?int $amount,
        string $reference
    ): QrCode {
        $qr = match ($type) {
            'STATIC' => $amount === null
                ? QrCode::static($alias, $country)
                : QrCode::staticWithAmount($alias, $country, $amount),
            'DYNAMIC' => QrCode::dynamic(
                $alias,
                $country,
                $amount ?? throw new MalformedPayload('un QR dynamique a besoin d\'un montant'),
                $reference !== '' ? $reference : throw new MalformedPayload(
                    'un QR dynamique a besoin d\'une référence de transaction'
                ),
            ),
            'TRANSFER' => QrCode::transfer($alias, $country),
        };

        if ($type !== 'DYNAMIC' && $reference !== '') {
            $qr = $qr->transactionId($reference);
        }

        return $qr->merchantName('DEMO PI-SPI')->merchantCity('X');
    }

    /**
     * Le QR, en SVG, avec le logo PI-SPI au centre.
     *
     * Niveau de correction H. Recouvrir le centre d'un logo détruit des
     * modules, et H est le seul niveau qui en reconstruit assez (environ 30
     * pour cent) pour que le code scanne quand même.
     */
    private function svg(string $payload): string
    {
        $writer = new Writer(
            new ImageRenderer(
                // Marge de 4 modules : c'est le minimum imposé par la norme
                // pour que le lecteur trouve les bords.
                new RendererStyle(330, 4),
                new SvgImageBackEnd()
            )
        );

        $svg = $writer->writeString(
            $payload,
            Encoder::DEFAULT_BYTE_MODE_ENCODING,
            ErrorCorrectionLevel::H()
        );

        return $this->withLogo($svg);
    }

    /**
     * Pose le logo au centre, sur une pastille blanche.
     *
     * Les coordonnées sont en pourcentage, donc indépendantes du viewBox choisi
     * par le moteur de rendu.
     */
    private function withLogo(string $svg): string
    {
        $logo = __DIR__.'/../assets/pi-spi-logo.png';

        if (! is_file($logo)) {
            return $svg;
        }

        $data = base64_encode((string) file_get_contents($logo));

        $overlay = '<rect x="40%" y="40%" width="20%" height="20%" rx="8" fill="#ffffff"/>'
            .'<image x="41.5%" y="41.5%" width="17%" height="17%" '
            .'preserveAspectRatio="xMidYMid meet" '
            .'href="data:image/png;base64,'.$data.'"/>';

        return str_replace('</svg>', $overlay.'</svg>', $svg);
    }

    /**
     * Les champs que la démo affiche, lus depuis le payload et non depuis la
     * saisie, pour que ce soit bien le QR qui parle.
     *
     * @return array<string, mixed>
     */
    private static function describe(Payload $payload): array
    {
        return [
            'gui' => $payload->gui(),
            'alias' => $payload->alias(),
            'pays' => $payload->country(),
            'devise' => $payload->currency(),
            'montant' => $payload->amount(),
            'canal' => $payload->channel(),
            'reference' => $payload->transactionId(),
            'beneficiaire' => $payload->merchantName(),
            'ville' => $payload->merchantCity(),
            'categorie' => $payload->merchantCategory(),
            'checksum' => $payload->checksum(),
            'champs' => $payload->fieldIds(),
        ];
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    private static function fail(string $message, int $status = 400): array
    {
        return ['status' => $status, 'body' => ['error' => $message]];
    }

    /** Utile à l'affichage : la devise et le GUI ne changent jamais. */
    public static function scheme(): array
    {
        return ['gui' => Spec::GUI, 'devise' => Spec::CURRENCY, 'exposant' => Spec::CURRENCY_EXPONENT];
    }
}
