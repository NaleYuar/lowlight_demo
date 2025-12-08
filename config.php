<?php
$DB_HOST = '127.0.0.1';
$DB_PORT = '3307';          
$DB_NAME = 'lowlight_demo_db';
$DB_USER = 'root';           
$DB_PASS = '';               

$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
