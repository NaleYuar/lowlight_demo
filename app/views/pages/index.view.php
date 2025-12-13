<?php

/**
 * 首頁 View（上傳與增亮）
 * - 顯示上傳表單
 * - 顯示分頁後的增亮紀錄卡片（透過 partials/record_cards.php）
 * - 顯示 flash message（msg=ok/deleted/upload_invalid...）
 */

/** @var array $rowsPage */
/** @var int $totalRecords */
/** @var int $totalPages */
/** @var int $page */
/** @var int $offset */
/** @var string $msg */
/** @var string $activeTab */

include APP_ROOT . '/views/layouts/header.php';
?>

<?php if ($msg === 'deleted'): ?>
    <div id="flash-message" class="alert alert-ok">已刪除紀錄。</div>
<?php elseif ($msg === 'delete_invalid' || $msg === 'notfound'): ?>
    <div id="flash-message" class="alert alert-error">刪除失敗：找不到對應紀錄或參數錯誤。</div>
<?php elseif ($msg === 'upload_invalid'): ?>
    <div id="flash-message" class="alert alert-error">上傳或增亮失敗，請確認檔案格式、大小或模型狀態。</div>
<?php elseif ($msg === 'ok'): ?>
    <div id="flash-message" class="alert alert-ok">圖片上傳並增亮完成。</div>
<?php endif; ?>

<section class="upload-card">
    <div>
        <h2 class="upload-main-title">上傳低光影像並執行增亮</h2>
        <p class="upload-subtitle">
            選擇一張低光影像，按下執行增亮按鈕，系統會進行增亮且將原圖與增亮結果紀錄在資料庫中。
        </p>

        <form action="upload" method="post" enctype="multipart/form-data">
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
        <form action="export" method="get">
            <button type="submit" class="btn-secondary">匯出 CSV</button>
        </form>
    </div>
</div>

<?php if ($totalRecords === 0): ?>
    <div class="empty-state">目前沒有任何紀錄。</div>
<?php else: ?>
    <div class="records-box">
        <div class="records-grid">
            <?php
            $rows = $rowsPage;
            $startIndex = $offset + 1;
            require APP_ROOT . '/Views/partials/record_cards.php';
            ?>
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

<?php include APP_ROOT . '/views/layouts/footer.php'; ?>
