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

## 🔗 Demo  (尚未上線)

👉 http://<your-vm-ip>/lowlight_demo/public/

---

## 📌 Overview  

本專案提供 **低光影像增亮（Low-Light Enhancement）** 的全端 Web 系統，  
整合：

- PHP（前端與 API）
- Python + PyTorch（模型推論）
- MySQL（紀錄 Log）
- GCP VM（雲端部署）

使用者上傳圖片後，系統會自動增亮並顯示結果。

---
## ✨ Features

### ⭐ Web-based Enhancement  
- 上傳圖片 → 即時增亮 → 顯示結果  

### ⭐ Deep Learning Integration  
- Python CLI 推論 (`enhance_cli.py`)  
- 可自由替換 `.pt` 權重  

### ⭐ MySQL Logging  
- 紀錄每次上傳與輸出結果  

### ⭐ GCP Deployment  
- Apache + PHP 前端  
- Python virtualenv 執行模型  
---

## 📁 專案架構

```text
lowlight_demo/
│
├── python_api/
│   ├── enhance_cli.py  # CLI 推論主程式
│   ├── model.py        # 模型架構
│   └── loss.py         # 計算損失
│ 
├── uploads/            # 使用者上傳圖片（ignored）
├── outputs/            # 模型輸出圖片（ignored）
├── weights/            # 模型權重 (ignored)
│
├── upload.php          # 上傳與處理流程
├── config.php          # MySQL連線 (ignored)
├── delete.php          # 刪除資料
│
├── README.md           
├── requirements.txt    # 環境所需套件
└── .gitignore
```
## ⚙️ 運作流程

```text
[Browser]
    │ 上傳影像
    ▼
[PHP: upload.php]
    │ 處存到/uploads資料夾
    │ 呼叫 Python CLI
    ▼
[Python Model]
    │ 增亮影像
    ▼
[PHP Backend]
    │ 存入MySQL資料庫
    ▼
[Web UI]
```
## 📝 Future Improvements
- Docker（PHP + MySQL + Python）
- 功能/介面 優化
- GitHub Actions 自動化
