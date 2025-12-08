<?php
require_once __DIR__ . '/config.php';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="lowlight_records_' . date('Ymd_His') . '.csv"');

echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

fputcsv($out, [
    'id',
    'orig_name',    
    'stored_name',  
    'created_at',
    'psnr',
    'ssim',
    'l1'
]);

$stmt = $pdo->query("SELECT * FROM images ORDER BY id ASC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($out, [
        $row['id'],
        $row['orig_name'],
        $row['stored_name'],
        $row['created_at'],
        $row['psnr'],
        $row['ssim'],
        $row['l1'],
    ]);
}

fclose($out);
exit;
