<?php
/**
 * 單一入口（Front Controller / Router）
 * - 依據 query 參數 r（或 rewrite 後的路由）分派到對應 Controller action
 * - 所有頁面與動作（index/metrics/upload/delete/export）都由此統一進入
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

$controller = $container['imageController'];

$route = $_GET['r'] ?? 'index';

switch ($route) {
    // routing
    case 'index':
        $controller->index();
        break;

    case 'metrics':
        $controller->metrics();
        break;

    case 'upload':
        // 僅允許 POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        $controller->upload();
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        $controller->delete();
        break;

    case 'export':
        $controller->exportCsv();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
