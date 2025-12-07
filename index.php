<?php
require_once 'config.php';

// 模型資訊
$modelName        = 'SCI Supervised Enhancement';
$modelVersion     = 'v1.0';
$modelStage       = 3;
$modelWeightsPath = 'python_api/weights/sci_supervised_best.pt';
$modelFramework   = 'PyTorch 2.x + Python 3.x';
$modelNote        = '針對低光道路場景微調，優化結構保留與抑制過曝。';

// 撈全部紀錄
$stmt = $pdo->query("SELECT * FROM images ORDER BY id ASC");
$rows = $stmt->fetchAll();

// 統計數量
$totalRecords = count($rows);

$msg = $_GET['msg'] ?? '';

function renderImageCards(array $rows): void {
    $counter = 1;

    foreach ($rows as $row) {
        $displayId = sprintf('LL-%05d', $counter);  
        // 原圖 / 增亮後路徑
        $origPath = 'uploads/' . $row['filename'];
        $enhPath  = $row['enhanced_path'];
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
                        <div class="thumb-label">原圖</div>
                        <img class="thumb"
                             src="<?php echo htmlspecialchars($origPath, ENT_QUOTES); ?>"
                             alt="original">
                    </div>
                    <div class="thumb-col">
                        <div class="thumb-label">增亮後</div>
                        <img class="thumb"
                             src="<?php echo htmlspecialchars($enhPath, ENT_QUOTES); ?>"
                             alt="enhanced">
                    </div>
                </div>

                <div class="card-actions">
                    <form action="delete.php" method="post" class="delete-form">
                        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                        <button type="submit" class="btn-danger">刪除紀錄</button>
                    </form>
                </div>
            </div>
        </article>
        <?php
        $counter++;
    }
}

?>
<!doctype html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>低光影像增亮 Demo 平台</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        :root {
            --cht-blue: #0072c6;
            --cht-blue-soft: #1890ff;
            --cht-cyan: #00a0af;
            --bg-page: #f5f7fb;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-sub: #4b5563;
            --border-soft: #e2e8f0;
            --danger: #dc2626;
            --radius-lg: 14px;
            --shadow-soft: 0 14px 34px rgba(15, 23, 42, 0.12);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            font-size: 17px;
        }

        a { color: inherit; text-decoration: none; }

        .top-nav-wrap {
            background: linear-gradient(90deg, #ffffff 0%, #f3f7fd 35%, #e6f4ff 100%);
            border-bottom: 1px solid #d0e2ff;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.12);
        }

        .top-nav {
            max-width: 1160px;
            margin: 0 auto;
            padding: 10px 18px 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-logo {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            background: radial-gradient(circle at 30% 0%, #e0f2fe, #0072c6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 750;
            font-size: 18px;
            box-shadow: 0 10px 22px rgba(0, 114, 198, 0.55);
        }

        .nav-title {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nav-title-main {
            font-size: 20px;
            font-weight: 650;
            letter-spacing: 0.02em;
        }

        .nav-title-sub {
            font-size: 13px;
            color: var(--text-sub);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .env-pill {
            border-radius: 999px;
            padding: 7px 14px;
            border: 1px solid #bae6fd;
            background: #eff6ff;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #0f172a;
        }

        .env-pill-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 9px #22c55e;
        }

        .subnav-wrap {
            max-width: 1160px;
            margin: 0 auto;
            padding: 0 18px 8px;
        }

        .subnav {
            display: flex;
            gap: 18px;
            border-bottom: 1px solid #d1e2ff;
            margin-top: 6px;
        }

        .subnav-item {
            padding: 8px 2px 10px;
            font-size: 15px;
            color: #64748b;
            position: relative;
            cursor: pointer;
            white-space: nowrap;
        }

        .subnav-item.active {
            color: var(--cht-blue);
            font-weight: 600;
        }

        .subnav-item.active::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -2px;
            height: 3px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--cht-blue), var(--cht-cyan));
        }

        .page {
            max-width: 1160px;
            margin: 0 auto;
            padding: 18px 18px 40px;
        }

        .alert {
            position: fixed;
            top: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;            

            padding: 10px 18px;
            border-radius: 999px;
            font-size: 15px;

            display: inline-flex;
            align-items: center;
            gap: 8px;

            border: 1px solid var(--border-soft);
            background: #ffffff;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.18);
            margin: 0;
        }


        .alert-ok {
            color: #166534;
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .alert-error {
            color: #b91c1c;
            border-color: #fecaca;
            background: #fef2f2;
        }

        .tab-section { display: none; }
        .tab-section.active { display: block; }

        .upload-card {
            background: var(--bg-card);
            border-radius: 18px;
            border: 1px solid var(--border-soft);
            box-shadow: var(--shadow-soft);
            padding: 20px 20px 16px;
            margin-bottom: 24px;
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(0, 1.1fr);
            gap: 18px;
        }

        @media (max-width: 768px) {
            .upload-card {
                grid-template-columns: minmax(0, 1fr);
                padding: 18px 14px 14px;
            }
        }

        .upload-main-title {
            font-size: 19px;
            margin: 0 0 6px;
            font-weight: 620;
        }

        .upload-subtitle {
            margin: 0 0 14px;
            font-size: 15px;
            color: var(--text-sub);
        }

        .form-row { margin-bottom: 14px; }

        .form-label {
            font-size: 15px;
            margin-bottom: 6px;
            display: block;
            font-weight: 500;
        }

        .file-input-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            inset: 0;
            cursor: pointer;
        }

        .file-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 9px 18px;
            border-radius: 999px;
            border: 1px solid #cbd5f5;
            background: #eff6ff;
            color: #075985;
            font-size: 15px;
            cursor: pointer;
            font-weight: 550;
        }

        .file-name {
            font-size: 14px;
            color: var(--text-sub);
        }

        .submit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 22px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, var(--cht-blue), var(--cht-cyan));
            color: #ffffff;
            font-size: 16px;
            font-weight: 650;
            cursor: pointer;
            box-shadow: 0 12px 26px rgba(0, 114, 198, 0.38);
            margin-top: 4px;
        }

        .submit-btn:active {
            transform: translateY(1px);
            box-shadow: 0 8px 20px rgba(0, 114, 198, 0.32);
        }

        .upload-meta {
            font-size: 14px;
            color: var(--text-sub);
            line-height: 1.7;
        }

        .upload-meta strong {
            font-weight: 600;
            color: #1f2933;
        }

        .upload-meta code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 13px;
            background: #edf2ff;
            border-radius: 999px;
            padding: 2px 8px;
            border: 1px solid #d0d7ff;
        }

        .section-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 18px 0 10px;
        }

        .section-title {
            font-size: 18px;
            margin: 0;
            font-weight: 620;
        }

        .section-count {
            font-size: 14px;
            color: var(--text-sub);
        }

        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 18px;
        }

        .card {
            border-radius: var(--radius-lg);
            background: #ffffff;
            border: 1px solid var(--border-soft);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
            padding: 0 0 10px;
            overflow: hidden;
        }

        .card-header-bar {
            height: 4px;
            background: linear-gradient(90deg, var(--cht-blue), var(--cht-cyan));
        }

        .card-inner { padding: 10px 12px 8px; }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge-id {
            font-size: 12px;
            padding: 2px 7px;
            border-radius: 999px;
            background: #eff6ff;
            color: #075985;
            border: 1px solid #bfdbfe;
        }

        .badge-model {
            font-size: 12px;
            padding: 2px 7px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #bbf7d0;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--text-sub);
            text-align: right;
        }

        .thumb-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }

        .thumb-col {
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 4px 4px 8px;
            background: #f9fafb;
        }

        .thumb-label {
            font-size: 14px;
            color: var(--text-sub);
            margin-bottom: 3px;
            font-weight: 500;
        }

        .thumb {
            width: 100%;
            height: auto;
            max-height: 280px;
            object-fit: contain;
            display: block;
            background: #e5e7eb;
            border-radius: 8px;
        }

        .card-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }

        .btn-danger {
            border-radius: 999px;
            border: 1px solid rgba(220, 38, 38, 0.4);
            background: #fef2f2;
            color: #b91c1c;
            padding: 5px 14px;
            font-size: 14px;
            cursor: pointer;
            font-weight: 520;
        }

        .btn-danger:active { transform: translateY(1px); }

        .empty-state {
            font-size: 15px;
            color: var(--text-sub);
            border-radius: 16px;
            border: 1px dashed var(--border-soft);
            padding: 16px;
            background: #ffffff;
        }

        /* 狀態 / 模型資訊卡片 */
        .status-grid {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1.5fr);
            gap: 18px;
        }

        @media (max-width: 900px) {
            .status-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .status-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid var(--border-soft);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
            padding: 14px 18px 16px;
            font-size: 14px;
        }

        .status-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 8px;
        }

        .status-list {
            margin: 0;
            padding-left: 20px;
        }

        .status-list li {
            margin: 4px 0;
        }

        footer {
            margin-top: 26px;
            font-size: 13px;
            color: var(--text-sub);
            text-align: center;
        }

        @media (max-width: 640px) {
            .top-nav {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-right {
                align-self: stretch;
                justify-content: flex-start;
            }
            .subnav {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<div class="top-nav-wrap">
    <div class="top-nav">
        <div class="nav-left">
            <div class="nav-logo">LL</div>
            <div class="nav-title">
                <div class="nav-title-main">低光影像增亮 Demo 平台</div>
                <div class="nav-title-sub">SCI 監督式模型 · PHP / Python / MySQL</div>
            </div>
        </div>
        <div class="nav-right">
            <div class="env-pill">
                <span class="env-pill-dot"></span>
                <span>本機測試環境</span>
            </div>
        </div>
    </div>
    <div class="subnav-wrap">
        <nav class="subnav" id="subnav">
            <div class="subnav-item active" data-target="tab-upload">上傳與增亮</div>
            <div class="subnav-item" data-target="tab-records">增亮紀錄</div>
            <div class="subnav-item" data-target="tab-status">系統狀態</div>
            <div class="subnav-item" data-target="tab-model">模型資訊</div>
        </nav>
    </div>
</div>

<div class="page">
    <?php if ($msg === 'deleted'): ?>
        <div id="flash-message" class="alert alert-ok">已刪除一筆紀錄與對應圖片檔。</div>
    <?php elseif ($msg === 'invalid' || $msg === 'notfound'): ?>
        <div id="flash-message" class="alert alert-error">刪除失敗：找不到對應紀錄或參數錯誤。</div>
    <?php elseif ($msg === 'ok'): ?>
        <div id="flash-message" class="alert alert-ok">圖片上傳並增亮完成。</div>
    <?php endif; ?>


    <!-- Tab 1：上傳與增亮 -->
    <section class="tab-section active" id="tab-upload">
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
                <strong>處理流程說明</strong><br>
                1. 圖片上傳後存入 <code>uploads/</code>。<br>
                2. PHP 直接呼叫 Python CLI：<code>python_api/enhance_cli.py</code>。<br>
                3. SCI 模型輸出增亮影像到 <code>outputs/</code>。<br>
                4. 將原圖檔名、增亮結果路徑與時間戳記新增到資料庫中。<br>
                5. 下方「增亮紀錄」區塊會即時展示影像增亮結果。
            </div>

        </section>

        <div class="section-title-row">
            <h2 class="section-title">最近上傳紀錄（摘要）</h2>
            <span class="section-count">共 <?php echo $totalRecords; ?> 筆</span>
        </div>

        <?php if ($totalRecords === 0): ?>
            <div class="empty-state">
                目前沒有任何紀錄。可以先上傳幾張 600 × 400 的低光影像，預覽增亮效果。
            </div>
        <?php else: ?>
            <div class="records-grid">
                <?php renderImageCards($rows); ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Tab 2：增亮紀錄（專門列表頁） -->
    <section class="tab-section" id="tab-records">
        <div class="section-title-row">
            <h2 class="section-title">增亮紀錄列表</h2>
            <span class="section-count">共 <?php echo $totalRecords; ?> 筆（排序：舊 → 新）</span>
        </div>

        <?php if ($totalRecords === 0): ?>
            <div class="empty-state">
                尚無任何紀錄，可由「上傳與增亮」頁面上傳新影像。
            </div>
        <?php else: ?>
            <div class="records-grid">
                <?php renderImageCards($rows); ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Tab 3：系統狀態 -->
    <section class="tab-section" id="tab-status">
        <div class="section-title-row">
            <h2 class="section-title">系統狀態</h2>
            <span class="section-count">Demo 用狀態總覽</span>
        </div>

        <div class="status-grid">
            <div class="status-card">
                <h3 class="status-title">服務狀態</h3>
                <ul class="status-list">
                    <li>Web 伺服器：Apache / XAMPP（本機）</li>
                    <li>後端語言：PHP <?php echo phpversion(); ?></li>
                    <li>資料庫：MySQL（資料表 <code>images</code>，目前 <?php echo $totalRecords; ?> 筆）</li>
                    <li>影像增亮：本機 Python CLI <code>python_api/enhance_cli.py</code>（由 PHP 透過 <code>shell_exec</code> 呼叫）</li>
                </ul>
            </div>

            <div class="status-card">
                <h3 class="status-title">操作說明</h3>
                <ul class="status-list">
                    <li>刪除紀錄時，會同步移除對應的原圖與增亮影像檔案。</li>
                    <li>本頁面可直接搬上 GCP VM 作為 Demo 平台。</li>
                    <li>未來可新增：Flask 健康檢查 / GPU 使用率 / 批次處理進度等資訊。</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Tab 4：模型資訊 -->
    <section class="tab-section" id="tab-model">
        <div class="section-title-row">
            <h2 class="section-title">模型資訊</h2>
            <span class="section-count">SCI 監督式增亮模型說明</span>
        </div>

        <div class="status-grid">
            <div class="status-card">
                <h3 class="status-title">基本資訊</h3>
                <ul class="status-list">
                    <li>模型名稱：<?php echo htmlspecialchars($modelName, ENT_QUOTES); ?></li>
                    <li>版本：<?php echo htmlspecialchars($modelVersion, ENT_QUOTES); ?></li>
                    <li>Stage 數量：<?php echo (int)$modelStage; ?></li>
                    <li>框架：<?php echo htmlspecialchars($modelFramework, ENT_QUOTES); ?></li>
                    <li>權重路徑：<code><?php echo htmlspecialchars($modelWeightsPath, ENT_QUOTES); ?></code></li>
                </ul>
            </div>

            <div class="status-card">
                <h3 class="status-title">訓練與應用說明</h3>
                <p style="margin: 4px 0 6px; font-size:14px; color:var(--text-sub);">
                    <?php echo htmlspecialchars($modelNote, ENT_QUOTES); ?>
                </p>
                <ul class="status-list">
                    <li>輸入：RGB 低光影像（預設 600 × 400）。</li>
                    <li>輸出：具備更佳亮度與對比的增亮結果，並盡量避免過曝與雜訊放大。</li>
                    <li>可作為後續 YOLO 物件偵測前處理步驟，提升夜間場景偵測率。</li>
                    <li>未來可在此區加入：Loss 設計、訓練資料集敘述、指標（PSNR / SSIM）摘要等。</li>
                </ul>
            </div>
        </div>
    </section>

    <footer>
        低光影像增亮 Demo · SCI 監督式 · © <?php echo date('Y'); ?>
    </footer>
</div>

<script>
    // 檔名顯示
    const fileInput = document.getElementById('file-input');
    const fileNameSpan = document.getElementById('file-name');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                fileNameSpan.textContent = this.files[0].name;
            } else {
                fileNameSpan.textContent = '尚未選擇檔案';
            }
        });
    }

    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const ok = confirm('確定要刪除這筆紀錄？對應的原圖與增亮影像也會一起移除。');
            if (!ok) e.preventDefault();
        });
    });

    // Tabs 切換
    const subnavItems = document.querySelectorAll('.subnav-item');
    const tabSections = document.querySelectorAll('.tab-section');

    subnavItems.forEach(item => {
        item.addEventListener('click', () => {
            const targetId = item.dataset.target;

            subnavItems.forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            tabSections.forEach(sec => {
                if (sec.id === targetId) sec.classList.add('active');
                else sec.classList.remove('active');
            });
        });
    });

    // 訊息自動關閉 
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
</script>
</body>
</html>