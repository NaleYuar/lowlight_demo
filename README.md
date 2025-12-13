<h1 align="center">🚀 Low-Light Image Enhancement Web System</h1>
<p align="center">
  <b>PHP + Python (PyTorch) + MySQL + GCP VM</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0-blue?logo=php">
  <img src="https://img.shields.io/badge/Python-3.10-yellow?logo=python">
  <img src="https://img.shields.io/badge/PyTorch-DeepLearning-red?logo=pytorch">
  <img src="https://img.shields.io/badge/MySQL-Database-orange?logo=mysql">
  <img src="https://img.shields.io/badge/GCP-VM-green?logo=googlecloud">
</p>

---

## 🔗 Demo

### 👉 https://lowlightdemo.me/

---

## 📌 Overview  
本系統採用 MVC（Model – View – Controller）架構進行設計，<br>
用於測試與驗證自行訓練之低光影像增亮模型權重，<br>
透過 Web 介面進行推論，觀察模型在不同低光影像上的增亮效果與品質指標。<br><br>
提供 **低光影像增亮（Low-Light Enhancement）** 的全端 Web 平台，整合：
- PHP（Web 前端與後端 Controller）
- Dockerized Python + PyTorch（SCI 模型推論 + 指標計算）
- MySQL（儲存影像紀錄與 PSNR / SSIM / L1 指標）
- GCP VM（雲端部署，CPU 推論）<br>
（考量雲端資源與預算限制，目前 GCP VM 以 CPU 執行模型推論）

➤ 使用者上傳低光影像後，系統會透過模型自動進行增亮，並計算對應的 PSNR、SSIM 與 L1 指標。<br>
➤ 結果會連同影像紀錄一併寫入資料庫，並在頁面下方的增亮紀錄區塊中呈現。<br>
➤ 由於本系統在 GCP VM 是用 CPU 進行深度學習推論，因此系統處理時間較長屬正常現象。

---
## 🖼 UI Preview

### 上傳與增亮頁面
![Upload & Enhancement Page](docs/ui_upload.png)

### 指標統計頁面
![Metrics Overview Page](docs/ui_metrics.png)
---

## ✨ Features

### ⭐ Web-based Enhancement  
- 上傳圖片 → Docker 模型增亮 → 顯示增亮影像
- 支援前後對比 Slider、圖片放大預覽、影像下載

### ⭐ Deep Learning Inside Docker
- SCI 增亮模型與所有 Python 套件皆封裝於 Docker 
- 不必自己設環境

### ⭐ Image Metrics (PSNR / SSIM / L1)
- 增亮後自動計算 3 種影像品質指標
- 影像指標支援匯出 CSV 功能

### ⭐ Records & Pagination
- 每頁顯示 6 筆增亮紀錄
- 顯示 PSNR、SSIM 與 L1 指標
- 支援刪除、下載、放大預覽、前後對比

### ⭐ MySQL Logging  
- 紀錄每次上傳與輸出結果  
---

## 📁 專案架構

```text
lowlight_demo/ 
│
├── index.php                 # 專案入口，轉導至 public/
│
├── public/                   
│   ├── index.php             # 單一入口 Router（Front Controller）
│   ├── .htaccess             # URL Rewrite 設定
│   ├── uploads/              # 使用者上傳的原始影像
│   └── outputs/              # 增亮後的輸出影像
│
├── app/
│   │
│   ├── services/
│   │   ├── DockerCli.php     # 封裝 docker 指令與 OS 路徑差異
│   │   └── EnhancePipeline.php # 上傳 → 增亮 → 計算指標的流程控制
│   │
│   ├── models/
│   │   └── ImageModel.php    # images 資料表的資料庫操作（上傳 / 刪除 / 分頁 / 統計）
│   │
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── footer.php    # 共用頁首與導覽列
│   │   │   └── header.php    # 共用頁尾與 JS
│   │   │ 
│   │   ├── pages/
│   │   │   ├── index.view.php   # 首頁（上傳 + 紀錄列表）
│   │   │   └── metrics.view.php # 指標統計頁
│   │   │ 
│   │   └── partials/
│   │       └── record_cards.php # 紀錄卡片的 UI 物件
│   │
│   ├── controllers/
│   │   └── ImageController.php  # 接收請求並呼叫 Model / Service
│   │
│   └── config/
│       ├── bootstrap.php        # 系統初始化與 DI 組裝
│       └── database.php         # PDO 資料庫連線設定
│
├── python_api/
│   ├── metrics_cli.py           # 計算 PSNR / SSIM / L1 的 CLI
│   ├── enhance_cli.py           # 增亮 CLI 主程式
│   ├── loss.py                  # 損失函式與 SSIM 計算
│   └── model.py                 # 模型架構
│
├── weights/
│   └── best.pt                  # 自己訓練完成的最佳模型權重
│
├── db/
│   └── schema.sql               # 資料庫 schema 定義
│
├── docker/
│   ├── Dockerfile               # Python 推論環境 Docker image
│   └── requirements.txt         # python環境所需套件
│
├── .gitignore                   # Git 忽略設定
│
└── README.md                    # 專案說明與使用文件
```
---
## ⚙️ 運作流程

```text
[Browser]
    │ 上傳低光影像
    ▼
[PHP Router / ImageController]
    │ 儲存檔案至 public/uploads
    │ 呼叫 Docker container → enhance_cli.py
    ▼
[Python (PyTorch Model)]
    │ 低光影像增亮
    │ 儲存輸出至 public/outputs
    ▼
[PHP Backend]
    │ 呼叫 Docker → metrics_cli.py
    │ 計算 PSNR / SSIM / L1
    │ 存入 MySQL 資料庫
    ▼
[Web UI]
    顯示增亮影像 + 影像品質指標與功能按鈕
    （放大/對比/刪除/下載）
```
---
## 🗄 資料庫設置 (MySQL)

### 1. 建立資料庫
```sql
CREATE DATABASE lowlight_demo_db;
```
### 2️. 匯入資料表 schema
```
mysql -u root -p lowlight_demo_db < db/schema.sql
```
---
## 📑 images 資料表結構說明

| 欄位名稱        | 說明                                   |
|-----------------|----------------------------------------|
| `id`            | 主鍵（AUTO_INCREMENT）                 |
| `orig_name`     | 上傳的原始影像檔名                     |
| `stored_name`   | 影像增亮後的檔名                 |
| `created_at`    | 上傳與影像增亮完成的時間                    |
| `psnr`          | Peak Signal-to-Noise Ratio（越高越好） |
| `ssim`          | Structural Similarity（越接近 1 越好） |
| `l1`            | L1 差異值（越低越好）                   |
---
## 🚀 Running the System

### 1. 安裝 Docker

### 2. 建置推論 Image：
```
docker build -t lowlight-python .
```
### 3. 建立 MySQL 資料庫並匯入db/schema.sql

### 4. 啟動 Apache（XAMPP / GCP VM）

### 5. 瀏覽器開啟：
```
http://localhost/lowlight_demo/public/
```
---
## 📝 Future Improvements
- UI / UX 介面優化 
- 支援批次影像增亮
