<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    header('Location: index.php?msg=upload_invalid');
    exit;
}

$uploadDir = __DIR__ . '/uploads';
$outputDir = __DIR__ . '/outputs';

if (!is_dir($uploadDir))  mkdir($uploadDir, 0777, true);
if (!is_dir($outputDir))  mkdir($outputDir, 0777, true);

$origName = $_FILES['image']['name'];       
$tmpName  = $_FILES['image']['tmp_name'];

$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowed, true)) {
    header('Location: index.php?msg=upload_invalid');
    exit;
}

// 產生「系統儲存檔名」：乾淨 + 不重複
$basename  = pathinfo($origName, PATHINFO_FILENAME);
$timestamp = date('Ymd_His');
$safeBase  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $basename);  // 去掉奇怪字元
$storedName = $safeBase . '_' . $timestamp . '.' . $ext;         // 存進 stored_name

// 實際路徑
$origFsPath = $uploadDir . '/' . $storedName;
$enhFsPath  = $outputDir . '/' . $storedName;

// Web 用相對路徑（給 Docker / <img> src 用）
$origRelPath = 'uploads/' . $storedName;
$enhRelPath  = 'outputs/' . $storedName;

if (!move_uploaded_file($tmpName, $origFsPath)) {
    header('Location: index.php?msg=upload_invalid');
    exit;
}

//呼叫 Docker 進行增亮
$projectRoot = __DIR__;

$dockerEnhCmd = sprintf(
    'docker run --rm -v %s:/workspace lowlight-python ' .
    'bash -lc "cd /workspace/python_api && python enhance_cli.py \'../%s\' \'../%s\' 2>&1"',
    escapeshellarg($projectRoot),
    str_replace('\\', '/', $origRelPath),
    str_replace('\\', '/', $enhRelPath)
);

$enhOutput = shell_exec($dockerEnhCmd);

// 檢查輸出檔是否存在
if (!file_exists($enhFsPath)) {
    @unlink($origFsPath);
    header('Location: index.php?msg=upload_invalid');
    exit;
}

// 呼叫 Docker 計算 PSNR / SSIM / L1 
$dockerMetricsCmd = sprintf(
    'docker run --rm -v %s:/workspace lowlight-python ' .
    'bash -lc "cd /workspace/python_api && python metrics_cli.py \'../%s\' \'../%s\' 2>&1"',
    escapeshellarg($projectRoot),
    str_replace('\\', '/', $origRelPath),
    str_replace('\\', '/', $enhRelPath)
);

$metricsJson = shell_exec($dockerMetricsCmd);

$psnr = $ssim = $l1 = null;

if ($metricsJson) {
    $m = json_decode($metricsJson, true);
    if (is_array($m) && empty($m['error'])) {
        $psnr = isset($m['psnr']) ? (float)$m['psnr'] : null;
        $ssim = isset($m['ssim']) ? (float)$m['ssim'] : null;
        $l1   = isset($m['l1'])   ? (float)$m['l1']   : null;
    }
}

//寫入資料庫
$stmt = $pdo->prepare("
    INSERT INTO images (orig_name, stored_name, created_at, psnr, ssim, l1)
    VALUES (:orig_name, :stored_name, NOW(), :psnr, :ssim, :l1)
");

$stmt->execute([
    ':orig_name'   => $origName,
    ':stored_name' => $storedName,
    ':psnr'        => $psnr,
    ':ssim'        => $ssim,
    ':l1'          => $l1,
]);

header('Location: index.php?msg=ok');
exit;
