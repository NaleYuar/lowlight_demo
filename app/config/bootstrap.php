<?php

declare(strict_types=1);

define('PROJECT_ROOT', dirname(__DIR__, 2));
define('APP_ROOT', PROJECT_ROOT . '/app');
define('PUBLIC_ROOT', PROJECT_ROOT . '/public');

date_default_timezone_set(getenv('LOWLIGHT_TIMEZONE') ?: 'Asia/Taipei');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/helpers/view.php';
require_once APP_ROOT . '/models/ImageModel.php';
require_once APP_ROOT . '/services/DockerCli.php';
require_once APP_ROOT . '/services/EnhancePipeline.php';
require_once APP_ROOT . '/services/SpreadsheetExporter.php';
require_once APP_ROOT . '/controllers/ImageController.php';

$imageRepo = new App\models\ImageModel($pdo);
$pipeline = new App\services\EnhancePipeline(
    $imageRepo,
    new App\services\DockerCli(getenv('LOWLIGHT_DOCKER_IMAGE') ?: 'lowlight-python')
);

$spreadsheetExporter = new App\services\SpreadsheetExporter();

$container = [
    'pdo' => $pdo,
    'imageRepo' => $imageRepo,
    'enhancePipeline' => $pipeline,
    'imageController' => new App\controllers\ImageController($imageRepo, $pipeline, $spreadsheetExporter),
];
