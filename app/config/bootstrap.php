<?php
/**
 * Bootstrap（專案啟動器）
 * - 定義路徑常數（PROJECT_ROOT / APP_ROOT / PUBLIC_ROOT）
 * - 載入資料庫連線設定（PDO）
 * - 載入 helper
 * - 建立簡易 DI container（組裝 Controller / Service / Repository）
 */

declare(strict_types=1);

// define PROJECT_ROOT / APP_ROOT / PUBLIC_ROOT ...
// require database.php
// require helpers
// build $container

define('PROJECT_ROOT', dirname(__DIR__, 2));
define('APP_ROOT', PROJECT_ROOT . '/app');
define('PUBLIC_ROOT', PROJECT_ROOT . '/public');

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/helpers/view.php';

$container = [];

require_once APP_ROOT . '/models/ImageModel.php';
require_once APP_ROOT . '/services/DockerCli.php';
require_once APP_ROOT . '/services/EnhancePipeline.php';
require_once APP_ROOT . '/controllers/ImageController.php';

$container['pdo'] = $pdo;

$container['imageRepo'] = new App\models\ImageModel($container['pdo']);
$container['dockerCli'] = new App\Services\DockerCli('lowlight-python');
$container['enhancePipeline'] = new App\Services\EnhancePipeline(
    $container['imageRepo'],
    $container['dockerCli']
);

$container['imageController'] = new App\Controllers\ImageController(
    $container['imageRepo'],
    $container['enhancePipeline']
);
