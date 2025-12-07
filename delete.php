<?php
require_once 'config.php';

if (!isset($_POST['id'])) {
    header("Location: index.php?msg=invalid");
    exit;
}

$id = (int)$_POST['id'];

$stmt = $pdo->prepare("SELECT filename, enhanced_path FROM images WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    header("Location: index.php?msg=notfound");
    exit;
}

$pathsToDelete = [
    'uploads/' . $row['filename'],
    $row['enhanced_path'],
];

foreach ($pathsToDelete as $relPath) {
    $abs = __DIR__ . DIRECTORY_SEPARATOR . $relPath;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

// 刪除資料庫紀錄
$stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php?msg=deleted");
exit;
