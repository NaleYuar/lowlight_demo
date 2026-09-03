(() => {
    const flash = document.querySelector('#flash-message');
    if (flash) {
        const dismiss = () => {
            flash.classList.add('leaving');
            window.setTimeout(() => flash.remove(), 220);
        };
        flash.querySelector('button')?.addEventListener('click', dismiss);
        window.setTimeout(dismiss, 4500);
    }

    const form = document.querySelector('#upload-form');
    const input = document.querySelector('#file-input');
    const dropZone = document.querySelector('#drop-zone');
    const preview = document.querySelector('#upload-preview');
    const summary = document.querySelector('#file-summary');
    const submit = document.querySelector('#submit-button');
    const status = document.querySelector('#upload-status');
    let previewUrl = null;

    const formatSize = (bytes) => bytes >= 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;

    const showFile = (file) => {
        if (!file) return;
        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            status.textContent = '只支援 JPG、PNG。';
            submit.disabled = true;
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            status.textContent = '圖片不可超過 10 MB。';
            submit.disabled = true;
            return;
        }

        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.hidden = false;
        summary.textContent = `${file.name} · ${formatSize(file.size)}`;
        summary.hidden = false;
        dropZone.classList.add('has-file');
        status.textContent = '圖片已選擇';
        submit.disabled = false;
    };

    input?.addEventListener('change', () => showFile(input.files?.[0]));
    ['dragenter', 'dragover'].forEach((name) => dropZone?.addEventListener(name, (event) => {
        event.preventDefault();
        dropZone.classList.add('drag-over');
    }));
    ['dragleave', 'drop'].forEach((name) => dropZone?.addEventListener(name, (event) => {
        event.preventDefault();
        dropZone.classList.remove('drag-over');
    }));
    dropZone?.addEventListener('drop', (event) => {
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
        showFile(file);
    });
    form?.addEventListener('submit', () => {
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.querySelector('.button-label').textContent = '處理中';
        status.textContent = '正在執行增亮，請稍候。';
    });

    document.querySelectorAll('.delete-form').forEach((deleteForm) => {
        deleteForm.addEventListener('submit', (event) => {
            if (!window.confirm('確定刪除這筆紀錄與圖片？')) event.preventDefault();
        });
    });

    let lastFocused = null;
    const openModal = (modal) => {
        lastFocused = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        modal.querySelector('.icon-button')?.focus();
    };
    const closeModal = (modal) => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        lastFocused?.focus?.();
    };

    const imageModal = document.querySelector('#image-modal');
    const modalImage = document.querySelector('#image-modal-img');
    const modalCaption = document.querySelector('#image-modal-caption');
    const modalDownload = document.querySelector('#image-modal-download');

    document.querySelectorAll('.image-preview').forEach((button) => {
        button.addEventListener('click', () => {
            modalImage.src = button.dataset.src;
            modalImage.alt = button.dataset.caption || '影像預覽';
            modalCaption.textContent = button.dataset.caption || '';
            modalDownload.href = button.dataset.src;
            openModal(imageModal);
        });
    });
    imageModal?.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', () => {
        closeModal(imageModal);
        modalImage.src = '';
    }));

    const compareModal = document.querySelector('#compare-modal');
    const compareStage = document.querySelector('#compare-stage');
    const compareOriginal = document.querySelector('#compare-original');
    const compareEnhanced = document.querySelector('#compare-enhanced');
    const compareSlider = document.querySelector('#compare-slider');
    const compareDialog = compareModal?.querySelector('.compare-dialog');
    const compareValues = {
        brightness: document.querySelector('#compare-brightness'),
        contrast: document.querySelector('#compare-contrast'),
        resolution: document.querySelector('#compare-resolution'),
        fileSize: document.querySelector('#compare-file-size'),
    };

    compareOriginal?.addEventListener('load', () => {
        if (compareOriginal.naturalWidth > 0 && compareOriginal.naturalHeight > 0) {
            const ratio = compareOriginal.naturalWidth / compareOriginal.naturalHeight;
            const maximumWidth = Math.min(960, window.innerWidth * 0.96);
            const maximumStageHeight = Math.max(180, window.innerHeight - 230);
            compareStage.style.setProperty('--image-aspect', `${compareOriginal.naturalWidth} / ${compareOriginal.naturalHeight}`);
            compareDialog.style.setProperty('--compare-width', `${Math.min(maximumWidth, maximumStageHeight * ratio)}px`);
        }
    });

    const updateCompare = (value) => {
        const percent = Math.max(0, Math.min(100, Number(value)));
        compareStage.style.setProperty('--compare-position', `${percent}%`);
        compareSlider.value = String(percent);
    };
    document.querySelectorAll('.compare-button').forEach((button) => {
        button.addEventListener('click', () => {
            compareOriginal.src = button.dataset.original;
            compareEnhanced.src = button.dataset.enhanced;
            Object.entries(compareValues).forEach(([key, element]) => {
                element.textContent = button.dataset[key] || '—';
            });
            updateCompare(50);
            openModal(compareModal);
        });
    });
    compareSlider?.addEventListener('input', () => updateCompare(compareSlider.value));
    compareStage?.addEventListener('pointerdown', (event) => {
        compareStage.setPointerCapture(event.pointerId);
        const setFromPointer = (pointerEvent) => {
            const rect = compareStage.getBoundingClientRect();
            updateCompare(((pointerEvent.clientX - rect.left) / rect.width) * 100);
        };
        setFromPointer(event);
        const move = (pointerEvent) => setFromPointer(pointerEvent);
        const stop = () => {
            compareStage.removeEventListener('pointermove', move);
            compareStage.removeEventListener('pointerup', stop);
        };
        compareStage.addEventListener('pointermove', move);
        compareStage.addEventListener('pointerup', stop);
    });
    compareModal?.querySelectorAll('[data-close-compare]').forEach((button) => button.addEventListener('click', () => {
        closeModal(compareModal);
        compareOriginal.src = '';
        compareEnhanced.src = '';
        compareStage.style.removeProperty('--image-aspect');
        compareDialog.style.removeProperty('--compare-width');
    }));

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (imageModal && !imageModal.hidden) closeModal(imageModal);
        if (compareModal && !compareModal.hidden) closeModal(compareModal);
    });

    window.addEventListener('beforeunload', () => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
    });
})();
