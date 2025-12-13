<?php
/**
 * Database 設定（PDO）
 * - 建立 PDO 連線並提供給全系統使用（由 bootstrap 引入）
 */

declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_PORT = '3307';
$DB_NAME = 'lowlight_demo_db';
$DB_USER = 'root';
$DB_PASS = '';

$dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
