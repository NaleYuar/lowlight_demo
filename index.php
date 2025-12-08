<?php
require_once __DIR__ . '/config.php';

// 分頁設定
$perPage = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// 總筆數
$totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
$totalPages   = max(1, (int)ceil($totalRecords / $perPage));

// 目前頁面的資料
$stmtPage = $pdo->prepare("SELECT * FROM images ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmtPage->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmtPage->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmtPage->execute();
$rowsPage = $stmtPage->fetchAll();

$msg = $_GET['msg'] ?? '';

function renderImageCards(array $rows): void {
    $counter = 1;

    foreach ($rows as $row) {
        $displayId = sprintf('LL-%05d', $counter);

        // 這裡改成用 stored_name 組出路徑
        $storedName = $row['stored_name'];

        $origRelPath = 'uploads/' . $storedName;
        $enhRelPath  = 'outputs/' . $storedName;

        $origFsPath = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $storedName;
        $enhFsPath  = __DIR__ . DIRECTORY_SEPARATOR . 'outputs' . DIRECTORY_SEPARATOR . $storedName;

        $origInfo = file_exists($origFsPath) ? @getimagesize($origFsPath) : null;
        $enhInfo  = file_exists($enhFsPath)  ? @getimagesize($enhFsPath)  : null;

        $origW = $origInfo[0] ?? null;
        $origH = $origInfo[1] ?? null;
        $enhW  = $enhInfo[0] ?? null;
        $enhH  = $enhInfo[1] ?? null;
        ?>
        <article class="card">
            <div class="card-header-bar"></div>
            <div class="card-inner">
                <div class="card-header">
                    <div class="card-title">
                        紀錄 <?php echo htmlspecialchars($displayId, ENT_QUOTES); ?>
                        <span class="badge-id">ID <?php echo (int)$row['id']; ?></span>
                        <span class="badge-model">SCI</span>
                    </div>
                    <div class="card-subtitle">
                        <?php echo htmlspecialchars($row['created_at'], ENT_QUOTES); ?>
                    </div>
                </div>

                <div class="thumb-row">
                    <div class="thumb-col">
                        <div class="thumb-label">
                            原圖
                            <span class="thumb-hint">（點擊可放大預覽）</span>
                        </div>
                        <img class="thumb"
                             src="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                             data-caption="原圖 - <?php echo htmlspecialchars($displayId, ENT_QUOTES); ?>"
                             data-orig="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                             data-enh="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>"
                             alt="original">
                    </div>
                    <div class="thumb-col">
                        <div class="thumb-label">
                            增亮後
                            <span class="thumb-hint">（點擊可放大預覽）</span>
                        </div>
                        <img class="thumb"
                             src="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>"
                             data-caption="增亮後 - <?php echo htmlspecialchars($displayId, ENT_QUOTES); ?>"
                             data-orig="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                             data-enh="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>"
                             alt="enhanced">
                    </div>
                </div>
                <div class="card-footer-row">
                    <?php
                    $psnrText = (isset($row['psnr']) && $row['psnr'] !== null)
                        ? number_format($row['psnr'], 2)
                        : '--';
                    $ssimText = (isset($row['ssim']) && $row['ssim'] !== null)
                        ? number_format($row['ssim'], 3)
                        : '--';
                    $l1Text = (isset($row['l1']) && $row['l1'] !== null)
                        ? number_format($row['l1'], 5)
                        : '--';
                    ?>
                    <div class="metric-row">
                        <span class="metric-pill metric-pill-psnr">
                            <span class="metric-label">PSNR</span>
                            <span class="metric-value"><?php echo $psnrText; ?></span>
                        </span>
                        <span class="metric-pill metric-pill-ssim">
                            <span class="metric-label">SSIM</span>
                            <span class="metric-value"><?php echo $ssimText; ?></span>
                        </span>
                        <span class="metric-pill metric-pill-l1">
                            <span class="metric-label metric-label-l1">L1</span>
                            <span class="metric-value"><?php echo $l1Text; ?></span>
                        </span>
                    </div>

                    <div class="card-actions">
                        <button type="button"
                                class="btn-secondary btn-compare"
                                data-orig="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                                data-enh="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>">
                            前後對比
                        </button>

                        <form action="delete.php" method="post" class="delete-form">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <button type="submit" class="btn-danger">刪除紀錄</button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
        <?php
        $counter++;
    }
}

$activeTab = 'upload';
include __DIR__ . '/View/layout_header.php';
?>

<?php if ($msg === 'deleted'): ?>
    <div id="flash-message" class="alert alert-ok">
        已刪除一筆紀錄與對應圖片檔。
    </div>

<?php elseif ($msg === 'delete_invalid' || $msg === 'notfound'): ?>
    <div id="flash-message" class="alert alert-error">
        刪除失敗：找不到對應紀錄或參數錯誤。
    </div>

<?php elseif ($msg === 'upload_invalid'): ?>
    <div id="flash-message" class="alert alert-error">
        上傳或增亮失敗，請確認檔案格式、大小或模型狀態。
    </div>

<?php elseif ($msg === 'ok'): ?>
    <div id="flash-message" class="alert alert-ok">
        圖片上傳並增亮完成。
    </div>
<?php endif; ?>

<section class="upload-card">
    <div>
        <h2 class="upload-main-title">上傳低光影像並執行增亮</h2>
        <p class="upload-subtitle">
             選擇一張低光影像，按下執行增亮按鈕，系統會進行增亮且將原圖與增亮結果紀錄在資料庫中。
        </p>

        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="form-row">
                <label class="form-label">選擇圖片檔案（JPG / PNG）：</label>
                <div class="file-input-wrapper">
                    <button type="button" class="file-btn">選擇檔案</button>
                    <input type="file" name="image" id="file-input" accept="image/*" required>
                    <span id="file-name" class="file-name">尚未選擇檔案</span>
                </div>
            </div>

            <button type="submit" class="submit-btn">執行增亮</button>
        </form>
    </div>

    <div class="upload-meta">
        <strong>系統開發目的：</strong><br>
          本平台用於測試與驗證自己訓練的低光增亮模型，透過套用自己訓練的權重進行推論，立即檢視在不同低光影像的增亮效果。<br><br>
        <strong>處理流程說明:</strong><br>
        1. 選擇一張低光影像並按下執行增亮按鈕，系統會自動完成增亮。<br>
        2. 下方資訊可直接預覽原圖與增亮後影像，可放大檢視與下載。<br>
        3. 前後對比按鈕，可開啟滑桿對比視窗，比較增亮前後的差異。<br>
        4. 刪除紀錄按鈕，可以移除對應影像與資料庫紀錄。<br>
        5. 由於是用CPU跑深度學習進行增亮，因此系統處理會較慢。
    </div>
</section>

<div class="section-title-row">
    <h2 class="section-title">增亮紀錄</h2>
    <div class="section-actions">
        <span class="section-count">
            共 <?php echo $totalRecords; ?> 筆，頁 <?php echo $page; ?> / <?php echo $totalPages; ?>，每頁 6 筆
        </span>
        <form action="export_csv.php" method="get">
            <button type="submit" class="btn-secondary">匯出 CSV</button>
        </form>
    </div>
</div>

<?php if ($totalRecords === 0): ?>
    <div class="empty-state">
        目前沒有任何紀錄。可以先上傳幾張 600 × 400 的低光影像，預覽增亮效果。
    </div>
<?php else: ?>
    <div class="records-box">
        <div class="records-grid">
            <?php renderImageCards($rowsPage, $offset + 1); ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-link">« 上一頁</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?php echo $p; ?>"
                       class="page-link<?php echo ($p === $page) ? ' current' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-link">下一頁 »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/View/layout_footer.php';
?>
