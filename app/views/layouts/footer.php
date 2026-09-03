    <footer class="site-footer">低光影像增亮工具 · CPU 推論</footer>
</main>

<div class="modal" id="image-modal" role="dialog" aria-modal="true" aria-labelledby="image-modal-title" hidden>
    <button class="modal-backdrop" type="button" data-close-modal aria-label="關閉預覽"></button>
    <div class="image-dialog">
        <header class="dialog-header">
            <h2 id="image-modal-title">影像預覽</h2>
            <button class="icon-button" type="button" data-close-modal aria-label="關閉">×</button>
        </header>
        <img id="image-modal-img" src="" alt="">
        <footer class="dialog-footer">
            <span id="image-modal-caption"></span>
            <a class="button button-light" id="image-modal-download" href="#" download>下載圖片</a>
        </footer>
    </div>
</div>

<div class="modal" id="compare-modal" role="dialog" aria-modal="true" aria-labelledby="compare-modal-title" hidden>
    <button class="modal-backdrop" type="button" data-close-compare aria-label="關閉比較"></button>
    <div class="compare-dialog">
        <header class="dialog-header">
            <h2 id="compare-modal-title">增亮前後比較</h2>
            <button class="icon-button" type="button" data-close-compare aria-label="關閉">×</button>
        </header>
        <div class="compare-stage" id="compare-stage" style="--compare-position: 50%">
            <img id="compare-enhanced" src="" alt="增亮後">
            <div class="compare-overlay">
                <img id="compare-original" src="" alt="原圖">
            </div>
            <div class="compare-divider" aria-hidden="true"><span>↔</span></div>
            <span class="compare-label label-before">原圖</span>
            <span class="compare-label label-after">增亮後</span>
        </div>
        <label class="compare-control" for="compare-slider">
            <span>原圖</span>
            <input id="compare-slider" type="range" min="0" max="100" value="50">
            <span>增亮後</span>
        </label>
        <div class="compare-stats" aria-label="增亮前後數值">
            <div><span>亮度</span><strong id="compare-brightness">—</strong></div>
            <div><span>對比</span><strong id="compare-contrast">—</strong></div>
            <div><span>解析度</span><strong id="compare-resolution">—</strong></div>
            <div><span>檔案大小</span><strong id="compare-file-size">—</strong></div>
        </div>
    </div>
</div>

<script src="assets/app.js?v=20260902-2" defer></script>
</body>
</html>
