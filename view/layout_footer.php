<?php
// 共用 Footer + Modals + JS
?>
    <footer>
        低光影像增亮 Demo · SCI 監督式 · Docker 部署 · © <?php echo date('Y'); ?>
    </footer>
</div>

<!-- 圖片放大預覽 Modal -->
<div class="img-modal" id="img-modal">
    <div class="img-modal-backdrop" id="img-modal-backdrop"></div>
    <div class="img-modal-inner">
        <button class="img-modal-close" id="img-modal-close" aria-label="關閉預覽">×</button>
        <img src="" alt="preview" class="img-modal-img" id="img-modal-img">
        <div class="img-modal-toolbar">
            <a href="#" id="img-modal-download" class="img-modal-download" download>下載此影像</a>
        </div>
        <div class="img-modal-caption" id="img-modal-caption"></div>
    </div>
</div>

<!-- Before/After 對比 Modal -->
<div class="compare-modal" id="compare-modal">
    <div class="compare-inner">
        <button class="compare-close" id="compare-close" aria-label="關閉對比視圖">×</button>
        <p class="compare-title">前後對比（滑條控制原圖 / 增亮比例）</p>
        <div class="compare-container" id="compare-container">
            <img src="" alt="original" class="compare-img" id="compare-orig">
            <img src="" alt="enhanced" class="compare-img compare-img-top" id="compare-enh">
        </div>
        <div class="compare-slider-wrap">
            <span>原圖</span>
            <input type="range" id="compare-slider" min="0" max="100" value="50">
            <span>增亮</span>
        </div>
    </div>
</div>

<script>
    const flash = document.getElementById('flash-message');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity 0.5s ease';
            flash.style.opacity = '0';
            setTimeout(() => {
                flash.remove();
            }, 500);
        }, 3000);

        const url = new URL(window.location.href);
        if (url.searchParams.has('msg')) {
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url.toString());
        }
    }

    // 檔名顯示
    const fileInput = document.getElementById('file-input');
    const fileNameSpan = document.getElementById('file-name');
    if (fileInput && fileNameSpan) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                fileNameSpan.textContent = this.files[0].name;
            } else {
                fileNameSpan.textContent = '尚未選擇檔案';
            }
        });
    }

    // 刪除確認
    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const ok = confirm('確定要刪除這筆紀錄？對應的原圖與增亮影像也會一起移除。');
            if (!ok) e.preventDefault();
        });
    });

    // 圖片放大預覽 + 下載
    const thumbs = document.querySelectorAll('.thumb');
    const modal = document.getElementById('img-modal');
    const modalImg = document.getElementById('img-modal-img');
    const modalCaption = document.getElementById('img-modal-caption');
    const modalClose = document.getElementById('img-modal-close');
    const modalBackdrop = document.getElementById('img-modal-backdrop');
    const modalDownload = document.getElementById('img-modal-download');

    function openImgModal(src, caption) {
        if (!modal) return;
        modalImg.src = src;
        modalCaption.textContent = caption || '';
        if (modalDownload) {
            modalDownload.href = src;
            const filename = src.split('/').pop().split('?')[0] || 'image.png';
            modalDownload.setAttribute('download', filename);
        }
        modal.classList.add('open');
    }

    function closeImgModal() {
        if (!modal) return;
        modal.classList.remove('open');
        modalImg.src = '';
        modalCaption.textContent = '';
        if (modalDownload) {
            modalDownload.href = '#';
        }
    }

    thumbs.forEach(img => {
        img.addEventListener('click', () => {
            const src = img.getAttribute('src');
            const caption = img.getAttribute('data-caption') || '';
            openImgModal(src, caption);
        });
    });

    if (modalClose && modalBackdrop) {
        modalClose.addEventListener('click', closeImgModal);
        modalBackdrop.addEventListener('click', closeImgModal);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.classList.contains('open')) {
            closeImgModal();
        }
    });

    // Before/After 對比 slider
    const compareModal = document.getElementById('compare-modal');
    const compareClose = document.getElementById('compare-close');
    const compareOrig = document.getElementById('compare-orig');
    const compareEnh = document.getElementById('compare-enh');
    const compareSlider = document.getElementById('compare-slider');
    const compareContainer = document.getElementById('compare-container');
    const compareButtons = document.querySelectorAll('.btn-compare');

    function setCompareRatio(percent) {
        if (!compareEnh) return;
        const p = Math.min(100, Math.max(0, percent));
        compareEnh.style.opacity = p / 100;
    }

    function openCompare(origSrc, enhSrc) {
        if (!compareModal) return;
        compareOrig.src = origSrc;
        compareEnh.src = enhSrc;
        compareSlider.value = 50;
        setCompareRatio(50);
        compareModal.classList.add('open');
    }

    function closeCompare() {
        if (!compareModal) return;
        compareModal.classList.remove('open');
        compareOrig.src = '';
        compareEnh.src = '';
    }

    compareButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const orig = btn.getAttribute('data-orig');
            const enh  = btn.getAttribute('data-enh');
            if (orig && enh) {
                openCompare(orig, enh);
            }
        });
    });

    if (compareClose) {
        compareClose.addEventListener('click', closeCompare);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && compareModal && compareModal.classList.contains('open')) {
            closeCompare();
        }
    });

    if (compareSlider) {
        compareSlider.addEventListener('input', () => {
            setCompareRatio(compareSlider.value);
        });
    }

    let compareDragging = false;
    if (compareContainer && compareSlider) {
        compareContainer.addEventListener('mousedown', (e) => {
            compareDragging = true;
            updateCompareFromEvent(e);
        });

        window.addEventListener('mouseup', () => {
            compareDragging = false;
        });

        window.addEventListener('mousemove', (e) => {
            if (!compareDragging) return;
            updateCompareFromEvent(e);
        });

        function updateCompareFromEvent(e) {
            const rect = compareContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            let percent = (x / rect.width) * 100;
            percent = Math.min(100, Math.max(0, percent));
            compareSlider.value = percent;
            setCompareRatio(percent);
        }
    }
</script>
</body>
</html>
