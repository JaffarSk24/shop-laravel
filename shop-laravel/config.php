<?php
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

return [
    'mailgun' => [
        'api_key' => $_ENV['MAILGUN_API_KEY'] ?? null,
        'domain'  => $_ENV['MAILGUN_DOMAIN'] ?? null,
    ]
];