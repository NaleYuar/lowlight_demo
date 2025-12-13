<?php

/**
 * Record Cards Partial（首頁紀錄卡片）
 * - 接收 $rows（資料庫查詢結果）與 $startIndex（分頁起始序號）
 * - 將每筆紀錄渲染成卡片：原圖/增亮圖、指標膠囊、對比與刪除操作
 */

/** @var array $rows */
/** @var int $startIndex */

$counter = $startIndex;

foreach ($rows as $row) {
    $displayId = sprintf('LL-%05d', $counter);
    $storedName = $row['stored_name'];

    // 相對 public/
    $origRelPath = 'uploads/' . $storedName;
    $enhRelPath  = 'outputs/' . $storedName;

    $origFsPath = PUBLIC_ROOT . '/uploads/' . $storedName;
    $enhFsPath  = PUBLIC_ROOT . '/outputs/' . $storedName;

    $origInfo = is_file($origFsPath) ? @getimagesize($origFsPath) : null;
    $enhInfo  = is_file($enhFsPath)  ? @getimagesize($enhFsPath)  : null;

    $psnrText = ($row['psnr'] ?? null) !== null ? number_format((float)$row['psnr'], 2) : '--';
    $ssimText = ($row['ssim'] ?? null) !== null ? number_format((float)$row['ssim'], 3) : '--';
    $l1Text   = ($row['l1']   ?? null) !== null ? number_format((float)$row['l1'], 5) : '--';
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
                    <?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES); ?>
                </div>
            </div>

            <div class="thumb-row">
                <div class="thumb-col">
                    <div class="thumb-label">原圖 <span class="thumb-hint">（點擊可放大預覽）</span></div>
                    <img class="thumb"
                         src="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                         data-caption="原圖 - <?php echo htmlspecialchars($displayId, ENT_QUOTES); ?>"
                         data-orig="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                         data-enh="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>"
                         alt="original">
                </div>
                <div class="thumb-col">
                    <div class="thumb-label">增亮後 <span class="thumb-hint">（點擊可放大預覽）</span></div>
                    <img class="thumb"
                         src="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>"
                         data-caption="增亮後 - <?php echo htmlspecialchars($displayId, ENT_QUOTES); ?>"
                         data-orig="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                         data-enh="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>"
                         alt="enhanced">
                </div>
            </div>

            <div class="card-footer-row">
                <div class="metric-row">
                    <span class="metric-pill metric-pill-psnr">
                        <span class="metric-label">PSNR</span><span class="metric-value"><?php echo $psnrText; ?></span>
                    </span>
                    <span class="metric-pill metric-pill-ssim">
                        <span class="metric-label">SSIM</span><span class="metric-value"><?php echo $ssimText; ?></span>
                    </span>
                    <span class="metric-pill metric-pill-l1">
                        <span class="metric-label metric-label-l1">L1</span><span class="metric-value"><?php echo $l1Text; ?></span>
                    </span>
                </div>

                <div class="card-actions">
                    <button type="button"
                            class="btn-secondary btn-compare"
                            data-orig="<?php echo htmlspecialchars($origRelPath, ENT_QUOTES); ?>"
                            data-enh="<?php echo htmlspecialchars($enhRelPath, ENT_QUOTES); ?>">
                        前後對比
                    </button>

                    <form action="delete" method="post" class="delete-form">
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
