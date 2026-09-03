<?php
/** @var array<int, array<string, mixed>> $rows 由首頁 View 傳入的增亮紀錄 */

$formatPair = static function (mixed $before, mixed $after, int $precision = 0): string {
    if ($before === null || $after === null) {
        return '—';
    }
    return number_format((float)$before, $precision) . ' → ' . number_format((float)$after, $precision) . '%';
};
$formatDuration = static function (mixed $milliseconds): string {
    if ($milliseconds === null) {
        return '—';
    }
    $value = (int)$milliseconds;
    return $value < 1000 ? $value . ' ms' : number_format($value / 1000, 1) . ' 秒';
};
$formatKilobytes = static function (mixed $kilobytes): string {
    if ($kilobytes === null) {
        return '—';
    }
    $value = (float)$kilobytes;
    return $value >= 1024
        ? number_format($value / 1024, 1) . ' MB'
        : number_format(max(1, (int)round($value))) . ' KB';
};
$formatSizePair = static function (mixed $before, mixed $after) use ($formatKilobytes): string {
    if ($before === null || $after === null) {
        return '—';
    }
    return $formatKilobytes($before) . ' → ' . $formatKilobytes($after);
};

foreach ($rows as $row):
    $storedName = (string)$row['stored_name'];
    $originalPath = 'uploads/' . rawurlencode($storedName);
    $enhancedPath = 'outputs/' . rawurlencode($storedName);
    $recordLabel = 'LL-' . str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT);
    $brightness = $formatPair($row['brightness_before_pct'] ?? null, $row['brightness_after_pct'] ?? null);
    $contrast = $formatPair($row['contrast_before_pct'] ?? null, $row['contrast_after_pct'] ?? null, 1);
    $fileSize = $formatSizePair($row['original_size_kb'] ?? null, $row['enhanced_size_kb'] ?? null);
    $width = (int)($row['image_width_px'] ?? 0);
    $height = (int)($row['image_height_px'] ?? 0);
    $resolution = $width > 0 && $height > 0 ? number_format($width) . ' × ' . number_format($height) : '—';
    $dimensionAttributes = $width > 0 && $height > 0 ? ' width="' . $width . '" height="' . $height . '"' : '';
?>
<article class="record-card">
    <header class="record-header">
        <div>
            <span class="record-id"><?= e($recordLabel) ?></span>
            <h3 title="<?= e($row['original_name']) ?>"><?= e($row['original_name']) ?></h3>
        </div>
        <div class="record-meta">
            <time><?= e($row['created_at']) ?></time>
            <span class="processing-chip"><?= e($formatDuration($row['processing_ms'] ?? null)) ?></span>
        </div>
    </header>

    <div class="image-pair">
        <button class="image-tile image-preview" type="button" data-src="<?= e($originalPath) ?>" data-caption="<?= e($recordLabel) ?> · 原圖">
            <span>原圖</span>
            <img src="<?= e($originalPath) ?>" alt="<?= e($row['original_name']) ?> 原圖" loading="lazy"<?= $dimensionAttributes ?>>
        </button>
        <button class="image-tile image-preview" type="button" data-src="<?= e($enhancedPath) ?>" data-caption="<?= e($recordLabel) ?> · 增亮後">
            <span>增亮後</span>
            <img src="<?= e($enhancedPath) ?>" alt="<?= e($row['original_name']) ?> 增亮後" loading="lazy"<?= $dimensionAttributes ?>>
        </button>
    </div>

    <div class="comparison-row" aria-label="增亮前後比較">
        <div class="comparison-item" title="增亮前後平均亮度"><span>亮度</span><strong><?= e($brightness) ?></strong></div>
        <div class="comparison-item" title="增亮前後亮暗差異"><span>對比</span><strong><?= e($contrast) ?></strong></div>
        <div class="comparison-item" title="原圖與增亮後的檔案大小"><span>檔案大小</span><strong><?= e($fileSize) ?></strong></div>
    </div>

    <footer class="record-actions">
        <button
            class="button button-primary compare-button"
            type="button"
            data-original="<?= e($originalPath) ?>"
            data-enhanced="<?= e($enhancedPath) ?>"
            data-brightness="<?= e($formatPair($row['brightness_before_pct'] ?? null, $row['brightness_after_pct'] ?? null, 1)) ?>"
            data-contrast="<?= e($contrast) ?>"
            data-resolution="<?= e($resolution) ?>"
            data-file-size="<?= e($fileSize) ?>"
        >滑動比較</button>
        <a class="button button-light" href="<?= e($enhancedPath) ?>" download>下載</a>
        <form action="index.php?r=delete" method="post" class="delete-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <button class="button button-danger" type="submit">刪除</button>
        </form>
    </footer>
</article>
<?php endforeach; ?>
