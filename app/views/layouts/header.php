<?php

/**
 * 共用 Header（Layout）
 * - 網站上方導覽列 + 全站 CSS
 * - 由各頁面 view include 使用
 * - 依 $activeTab 決定目前選取狀態（upload/metrics）
 */

if (!isset($activeTab)) {
    $activeTab = 'upload';
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
            margin-top: 40px; 
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
            padding: 9px 18px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, var(--cht-blue), var(--cht-cyan));
            color: #ffffff;
            font-size: 16px;
            font-weight: 650;
            cursor: pointer;
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

        .section-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .records-box {
            margin-top: 6px;
            padding: 12px 12px 14px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid var(--border-soft);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
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
            gap: 10px;
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
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .thumb-hint {
            font-size: 11px;
            color: #9ca3af;
        }

        .thumb {
            width: 100%;
            height: auto;
            max-height: 280px;
            object-fit: contain;
            display: block;
            background: #e5e7eb;
            border-radius: 8px;
            cursor: zoom-in;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }

        .thumb:hover {
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
            transform: translateY(-1px);
        }

        .card-footer-row {
            margin-top: 7px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
            color: var(--text-sub);
        }

        .metric-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .metric-pill {
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        /* 指標標題：加粗 */
        .metric-label {
            font-weight: 650;
            font-size: 13px;
        }

        /* L1 標題再大一點 */
        .metric-label-l1 {
            font-size: 14px;
        }

        /* 在 PSNR / SSIM / L1 後面自動加上 ":" */
        .metric-label::after {
            content: ":";
            margin: 0 2px 0 2px;
        }

        /* 三種不同底色的膠囊 */
        .metric-pill-psnr {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .metric-pill-ssim {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #047857;
        }

        .metric-pill-l1 {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .metric-value {
            font-variant-numeric: tabular-nums;
        }

        .img-meta-inline {
            display: flex;
            align-items: baseline;
            gap: 6px;
            flex-wrap: wrap;
        }

        .img-meta-label {
            font-weight: 600;
        }

        .img-meta-sep {
            color: #9ca3af;
        }

        .card-actions {
            display: flex;
            gap: 8px;
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

        .btn-secondary {
            border-radius: 999px;
            border: 1px solid #cbd5f5;
            background: #f9fafb;
            color: #0369a1;
            padding: 5px 12px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-secondary:active {
            transform: translateY(1px);
        }

        .empty-state {
            font-size: 15px;
            color: var(--text-sub);
            border-radius: 16px;
            border: 1px dashed var(--border-soft);
            padding: 16px;
            background: #ffffff;
        }

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

        .pagination {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .page-link {
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #cbd5f5;
            background: #eff6ff;
            font-size: 13px;
            color: #0369a1;
            text-decoration: none;
        }

        .page-link.current {
            background: var(--cht-blue);
            border-color: var(--cht-blue);
            color: #ffffff;
            font-weight: 600;
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

        /* 圖片放大預覽 Modal */
        .img-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9990;
        }

        .img-modal.open {
            display: flex;
        }

        .img-modal-inner {
            position: relative;
            max-width: 90vw;
            max-height: 90vh;
            background: #020617;
            border-radius: 12px;
            padding: 10px 10px 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
        }

        .img-modal-img {
            max-width: 100%;
            max-height: 80vh;
            display: block;
            border-radius: 8px;
        }

        .img-modal-caption {
            margin-top: 6px;
            font-size: 13px;
            color: #e5e7eb;
            text-align: left;
        }

        .img-modal-close {
            position: absolute;
            top: 6px;
            right: 8px;
            border: none;
            background: rgba(15, 23, 42, 0.7);
            color: #e5e7eb;
            width: 26px;
            height: 26px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }

        .img-modal-backdrop {
            position: absolute;
            inset: 0;
        }

        .img-modal-toolbar {
            margin-top: 6px;
            display: flex;
            justify-content: flex-end;
        }

        .img-modal-download {
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #0f172a;
            color: #e5e7eb;
            padding: 4px 10px;
            font-size: 12px;
            text-decoration: none;
        }

        .img-modal-download:hover {
            background: #1e293b;
        }

        /* Before/After 對比 Modal */
        .compare-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9991;
        }

        .compare-modal.open {
            display: flex;
        }

        .compare-inner {
            position: relative;
            max-width: 900px;
            width: 90vw;
            background: #020617;
            border-radius: 12px;
            padding: 12px 14px 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.7);
            color: #e5e7eb;
        }

        .compare-close {
            position: absolute;
            top: 6px;
            right: 8px;
            border: none;
            background: rgba(15, 23, 42, 0.7);
            color: #e5e7eb;
            width: 26px;
            height: 26px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }

        .compare-title {
            font-size: 14px;
            margin: 0 0 8px;
        }

        .compare-container {
            position: relative;
            width: 100%;
            aspect-ratio: 3 / 2;
            overflow: hidden;
            background: #020617;
        }

        .compare-img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
            user-select: none;
        }

        .compare-img-top {
            opacity: 0.5;
            transition: opacity 0.08s linear;
        }

        .compare-slider-wrap {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .compare-slider-wrap input[type="range"] {
            flex: 1;
        }

        /* 下方平均值專用卡片 */
        .metrics-avg-card {
            margin-top: 6px;
            padding-top: 14px;
            padding-bottom: 1px;
        }

        .metrics-avg-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 6px;
        }

        .metrics-avg-pill {
            flex: 1 1 160px;
            min-width: 0;
            border-radius: 999px;
            padding: 8px 14px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 13px;
            font-variant-numeric: tabular-nums;
        }

        .metrics-avg-label {
            font-size: 13px;
            font-weight: 600;
        }

        .metrics-avg-value {
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .metrics-avg-unit {
            font-size: 12px;
            color: #6b7280;
        }

        .metrics-avg-pill-psnr {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .metrics-avg-pill-ssim {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #047857;
        }

        .metrics-avg-pill-l1 {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .metrics-avg-note {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
        }

        @media (max-width: 640px) {
            .metrics-intro-head {
                flex-direction: column;
                align-items: flex-start;
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
                <div class="nav-title-sub">SCI 監督式模型 · PHP / Docker / Python / MySQL</div>
            </div>
        </div>
        <div class="nav-right">
            <div class="env-pill">
                <span class="env-pill-dot"></span>
                <span>Demo 測試環境</span>
            </div>
        </div>
    </div>
    <div class="subnav-wrap">
        <nav class="subnav">
            <a class="subnav-item<?= $activeTab === 'upload' ? ' active' : '' ?>" href="index.php">上傳與增亮</a>
            <a class="subnav-item<?= $activeTab === 'metrics' ? ' active' : '' ?>" href="metrics">指標統計</a>
        </nav>
    </div>
</div>

<div class="page">
