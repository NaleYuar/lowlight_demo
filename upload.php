<?php
require_once 'config.php';

/**
 * 1. 收使用者上傳的圖片
 * 2. 把檔案存到 uploads/
 * 3. 呼叫 Python 做增亮，輸出到 outputs/
 * 4. 把檔名與增亮後路徑寫進資料庫，最後導回首頁
 */

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    die('上傳失敗，請重新選擇圖片。');
}

$fileInfo = $_FILES['image'];


$originalName = $fileInfo['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));


$basename      = date('Ymd_His') . '_' . bin2hex(random_bytes(4));
$savedFilename = $basename . '.' . $ext;


$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);

}

$inputPath = $uploadDir . DIRECTORY_SEPARATOR . $savedFilename;


if (!move_uploaded_file($fileInfo['tmp_name'], $inputPath)) {
    die('無法儲存上傳檔案，請確認 uploads 資料夾權限。');
}


$outputDir = __DIR__ . DIRECTORY_SEPARATOR . 'outputs';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$outputName = $basename . '_out.png';
$outputPath = $outputDir . DIRECTORY_SEPARATOR . $outputName;

$PYTHON_BIN = 'D:\\Anaconda\\envs\\YOLO\\python.exe';

$PY_SCRIPT = __DIR__ . DIRECTORY_SEPARATOR . 'python_api' . DIRECTORY_SEPARATOR . 'enhance_cli.py';

$cmd = '"' . $PYTHON_BIN . '" ' .
    escapeshellarg($PY_SCRIPT) . ' ' .
    escapeshellarg($inputPath) . ' ' .
    escapeshellarg($outputPath);

$log = shell_exec($cmd . ' 2>&1');

if (!file_exists($outputPath)) {
    echo "增亮失敗，Python log 如下：<pre>" . htmlspecialchars($log) . "</pre>";
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO images (filename, enhanced_path, created_at)
    VALUES (?, ?, NOW())
");

$stmt->execute([
    $savedFilename,
    'outputs/' . $outputName
]);

header("Location: index.php");
exit;
