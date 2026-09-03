<?php

declare(strict_types=1);

$dbHost = getenv('LOWLIGHT_DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('LOWLIGHT_DB_PORT') ?: '3307';
$dbName = getenv('LOWLIGHT_DB_NAME') ?: 'lowlight_demo_db';
$dbUser = getenv('LOWLIGHT_DB_USER') ?: 'root';
$dbPass = getenv('LOWLIGHT_DB_PASS') ?: '';

$pdo = new PDO(
    "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
