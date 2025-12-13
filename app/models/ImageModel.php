<?php

/**
 * ImageModel
 * - 資料模型層（Model）
 * - 負責 images 資料表的所有資料庫操作（CRUD、分頁、統計）
 */

declare(strict_types=1);

namespace app\models;

use PDO;

final class ImageModel
{

    /**
     * @param PDO $pdo 由 bootstrap 建立並注入（同一條 DB 連線）
     */

    public function __construct(private PDO $pdo) {}

    /**
     * 取得 images 總筆數
     * - 用途：分頁計算 totalPages / 顯示總紀錄數
     */

    public function countAll(): int
    {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM images")->fetchColumn();
    }

    /**
     * 取得指定頁面的資料
     * - 用途：首頁紀錄列表
     *
     * @return array<int, array<string, mixed>> rows
     */

    public function fetchPage(int $limit, int $offset, string $orderBy = 'id', string $dir = 'ASC'): array
    {
        $orderBy = in_array($orderBy, ['id', 'created_at'], true) ? $orderBy : 'id';
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM images ORDER BY {$orderBy} {$dir} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 依 id 查詢 stored_name
     * - 用途：刪除流程需要用 stored_name 拼出 uploads/outputs 檔案路徑
     * - 回傳：找不到時回 null
     */

    public function findStoredNameById(int $id): ?string
    {
        $stmt = $this->pdo->prepare("SELECT stored_name FROM images WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['stored_name'] ?? null;
    }

    /**
     * 依 id 刪除單筆資料庫紀錄
     * - 用途：刪除按鈕（Controller 會先刪檔，再刪 DB）
     */

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM images WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    /**
     * 新增一筆影像處理紀錄
     * - 用途：upload 流程完成後寫入（檔名、建立時間、PSNR/SSIM/L1）
     * - 指標可為 null：代表 metrics 計算失敗或未產生
     */

    public function insertRecord(string $origName, string $storedName, ?float $psnr, ?float $ssim, ?float $l1): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO images (orig_name, stored_name, created_at, psnr, ssim, l1)
            VALUES (:orig_name, :stored_name, NOW(), :psnr, :ssim, :l1)
        ");

        $stmt->execute([
            ':orig_name' => $origName,
            ':stored_name' => $storedName,
            ':psnr' => $psnr,
            ':ssim' => $ssim,
            ':l1' => $l1,
        ]);
    }

    /**
     * 取得整體平均指標（metrics 頁使用）
     * - 用途：顯示全體 PSNR/SSIM/L1 的平均值
     * - 回傳：若表內無資料則回傳全 null
     *
     * @return array{psnr_avg: mixed, ssim_avg: mixed, l1_avg: mixed}
     */

    public function avgAll(): array
    {
        $row = $this->pdo->query("
            SELECT AVG(psnr) AS psnr_avg, AVG(ssim) AS ssim_avg, AVG(l1) AS l1_avg
            FROM images
        ")->fetch(PDO::FETCH_ASSOC);

        return $row ?: ['psnr_avg' => null, 'ssim_avg' => null, 'l1_avg' => null];
    }

    /**
     * 取得全部資料（依 id ASC）
     * - 用途：匯出 CSV（保持順序穩定，方便比對）
     *
     * @return array<int, array<string, mixed>>
     */

    public function fetchAllAsc(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM images ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
