<?php
require_once __DIR__ . '/config.php';

$totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();

$avgAll = $pdo->query("
    SELECT AVG(psnr) AS psnr_avg, AVG(ssim) AS ssim_avg, AVG(l1) AS l1_avg
    FROM images
")->fetch();

$activeTab = 'metrics';
include __DIR__ . '/View/layout_header.php';
?>

<div class="section-title-row">
    <h2 class="section-title">指標統計總覽</h2>
    <span class="section-count">目前共 <?php echo $totalRecords; ?> 筆紀錄</span>
</div>

<?php if ($totalRecords === 0): ?>
    <div class="empty-state">
        尚無任何紀錄，請先在「上傳與增亮」頁上傳影像並完成增亮後，再查看指標統計。
    </div>
<?php else: ?>
    <section class="status-card metrics-avg-card">
        <h3 class="status-title">整體平均指標</h3>

        <div class="metrics-avg-row">
            <div class="metrics-avg-pill metrics-avg-pill-psnr">
                <div class="metrics-avg-label">PSNR 平均</div>
                <div class="metrics-avg-value">
                    <?php echo $avgAll['psnr_avg'] !== null ? number_format($avgAll['psnr_avg'], 2) : '--'; ?>
                    <span class="metrics-avg-unit">dB</span>
                </div>
            </div>

            <div class="metrics-avg-pill metrics-avg-pill-ssim">
                <div class="metrics-avg-label">SSIM 平均</div>
                <div class="metrics-avg-value">
                    <?php echo $avgAll['ssim_avg'] !== null ? number_format($avgAll['ssim_avg'], 3) : '--'; ?>
                </div>
            </div>

            <div class="metrics-avg-pill metrics-avg-pill-l1">
                <div class="metrics-avg-label">L1 平均</div>
                <div class="metrics-avg-value">
                    <?php echo $avgAll['l1_avg'] !== null ? number_format($avgAll['l1_avg'], 5) : '--'; ?>
                </div>
            </div>
        </div>

        <p class="metrics-avg-note">
            以上為目前資料庫中所有紀錄的平均值。
        </p>
    </section>

<?php endif; ?>

<?php
include __DIR__ . '/View/layout_footer.php';
