<?php

declare(strict_types=1);

use PiSpi\Demo\Demo;

require __DIR__.'/../vendor/autoload.php';

$demo = new Demo();
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/** Lit un corps JSON, ou un formulaire classique, indifféremment. */
$input = static function (): array {
    $raw = file_get_contents('php://input');

    if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return $_POST;
};

$json = static function (array $result): void {
    http_response_code($result['status']);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};

if ($method === 'POST' && $path === '/generate') {
    $json($demo->generate($input()));

    return;
}

if ($method === 'POST' && $path === '/decode') {
    $json($demo->decode($input()));

    return;
}

if ($path !== '/') {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Route inconnue'], JSON_UNESCAPED_UNICODE);

    return;
}

$countries = Demo::COUNTRIES;
$types = Demo::TYPES;
$scheme = Demo::scheme();

require __DIR__.'/../templates/index.php';
