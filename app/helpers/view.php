<?php

/**
 * View / Redirect Helper
 * - render(): 載入 view 檔案並傳入資料（extract）
 * - redirect(): 統一處理 header Location 與 exit
 */

declare(strict_types=1);

/**
 * 渲染指定 view 檔案
 * @param string $viewPath 例如 APP_ROOT . '/views/pages/index.view.php'
 * @param array<string, mixed> $data
 */

function render(string $viewFile, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/views/' . ltrim($viewFile, '/');
}


/**
 * 重新導向（可傳相對路徑）
 */

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}
