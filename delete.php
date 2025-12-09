<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: index.php?msg=invalid");
    exit;
}

$id = (int)$_POST['id'];

$stmt = $pdo->prepare("SELECT orig_name, stored_name FROM images WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    header("Location: index.php?msg=notfound");
    exit;
}

$pathsToDelete = [];

if (!empty($row['stored_name'])) {
    $pathsToDelete[] = __DIR__ . '/uploads/' . $row['stored_name'];
    $pathsToDelete[] = __DIR__ . '/outputs/' . $row['stored_name'];
}

foreach ($pathsToDelete as $absPath) {
    if (is_file($absPath)) {
        @unlink($absPath);
    }
}

// 刪除資料庫紀錄
$stmt = $pdo->prepare("DELETE FROM images WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php?msg=deleted");
exit;
