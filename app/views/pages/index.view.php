<?php
/**
 * @var array{type: string, message: string}|null $flash
 * @var array<int, array<string, mixed>> $rowsPage
 * @var int $page
 * @var int $totalRecords
 * @var int $totalPages
 */
include APP_ROOT . '/views/layouts/header.php';
?>

<?php if ($flash !== null): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" id="flash-message" role="status">
        <?= e($flash['message']) ?>
        <button type="button" aria-label="關閉訊息">×</button>
    </div>
<?php endif; ?>

<section class="intro">
    <h1>低光影像增亮</h1>
    <p>選擇 JPG 或 PNG 圖片，執行增亮後即可查看、比較及下載結果。</p>
</section>

<section class="panel upload-panel" aria-labelledby="upload-title">
    <div class="panel-header">
        <div>
            <h2 id="upload-title">上傳圖片</h2>
            <p>檔案上限 10 MB、400 萬像素。</p>
        </div>
    </div>

    <form id="upload-form" action="index.php?r=upload" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="drop-zone" id="drop-zone" for="file-input">
            <input type="file" name="image" id="file-input" accept="image/jpeg,image/png" required>
            <span class="upload-symbol" aria-hidden="true">＋</span>
            <span class="drop-text"><strong>選擇或拖曳圖片</strong><small>支援 JPG、PNG</small></span>
            <img id="upload-preview" src="" alt="待上傳圖片預覽" hidden>
            <span class="file-summary" id="file-summary" hidden></span>
        </label>
        <div class="upload-actions">
            <span id="upload-status" aria-live="polite">尚未選擇圖片</span>
            <button class="button button-primary" id="submit-button" type="submit" disabled>
                <span class="button-label">執行增亮</span>
                <span class="spinner" aria-hidden="true"></span>
            </button>
        </div>
    </form>
</section>

<section class="records" aria-labelledby="records-title">
    <div class="section-header">
        <div>
            <h2 id="records-title">增亮紀錄</h2>
            <span>共 <?= number_format($totalRecords) ?> 筆</span>
        </div>
        <?php if ($totalRecords > 0): ?>
            <a class="button button-light" href="index.php?r=export">匯出 Excel</a>
        <?php endif; ?>
    </div>

    <?php if ($totalRecords === 0): ?>
        <div class="empty-state">尚無增亮紀錄。</div>
    <?php else: ?>
        <div class="records-grid">
            <?php $rows = $rowsPage; require APP_ROOT . '/views/partials/record_cards.php'; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="紀錄分頁">
                <a class="page-button<?= $page <= 1 ? ' disabled' : '' ?>" href="?page=<?= max(1, $page - 1) ?>">上一頁</a>
                <span><?= $page ?> / <?= $totalPages ?></span>
                <a class="page-button<?= $page >= $totalPages ? ' disabled' : '' ?>" href="?page=<?= min($totalPages, $page + 1) ?>">下一頁</a>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php include APP_ROOT . '/views/layouts/footer.php'; ?>
