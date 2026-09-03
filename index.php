<?php
/**
 * 專案根目錄入口（非實際執行頁面）
 * - 將所有請求導向 public/（真正的 Web 入口）
 * - 用於避免直接存取專案根目錄造成路徑/資安風險
 */
header('Location: public/index.php');
exit;
