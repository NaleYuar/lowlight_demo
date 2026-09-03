<?php
/**
 * 單一入口（Front Controller / Router）
 * - 依據 query 參數 r（或 rewrite 後的路由）分派到對應 Controller action
 * - 所有頁面與動作（index/upload/delete/export）都由此統一進入
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; img-src 'self' blob: data:; style-src 'self' 'unsafe-inline'; script-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");

$controller = $container['imageController'];

$route = is_string($_GET['r'] ?? null) ? $_GET['r'] : 'index';

switch ($route) {
    // routing
    case 'index':
        $controller->index();
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
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            exit('405 Method Not Allowed');
        }
        $controller->exportSpreadsheet();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
