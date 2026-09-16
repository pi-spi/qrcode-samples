<?php

declare(strict_types=1);

use PiSpi\Sample\InvalidInput;
use PiSpi\Sample\Qr;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (! is_file($autoload)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Dependances absentes. Lancez composer install dans le dossier php.\n");
}

require $autoload;

$qr = new Qr();

// Le serveur integre de PHP sert les fichiers presents sur le disque et
// n appelle ce script que pour le reste, donc il n y a rien a faire ici pour
// les images. Les chemins reprennent ceux de la demo Django pour que les deux
// implementations repondent aux memes requetes.
$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '') {
    $countries = Qr::COUNTRIES;
    $types = Qr::TYPES;

    require dirname(__DIR__) . '/templates/index.php';
    exit;
}

if ($path !== '/generate' && $path !== '/decode') {
    json(['error' => 'Page inconnue.'], 404);
}

if ($method !== 'POST') {
    json(['error' => 'Methode non autorisee'], 405);
}

$body = json_decode(file_get_contents('php://input') ?: '', true);

if (! is_array($body)) {
    json(['error' => 'Corps de la requete JSON invalide.'], 400);
}

try {
    json($path === '/generate'
        ? $qr->generate($body)
        : $qr->decode((string) ($body['payload'] ?? '')));
} catch (InvalidInput $e) {
    $error = ['error' => $e->getMessage()];

    if ($e->step() !== null) {
        $error['step'] = $e->step();
    }

    json($error, $e->status());
}

/**
 * @param  array<string, mixed>  $data
 */
function json(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
