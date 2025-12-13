<?php
/**
 * ImageController
 * - 負責接收請求、驗證輸入、呼叫 Service/Repository、最後 render view 或 redirect
 */

declare(strict_types=1);

namespace app\Controllers;

use App\models\ImageModel;
use App\services\EnhancePipeline;

final class ImageController
{
    public function __construct(
        private ImageModel $repo,
        private EnhancePipeline $pipeline
    ) {}

    /**
     * 首頁：上傳區 + 分頁紀錄列表
     * GET /
     * - 計算分頁資訊
     * - 讀取當頁資料
     * - 將資料交給 view 
     */

    public function index(): void
    {
        $perPage = 6;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;

        $totalRecords = $this->repo->countAll();
        $totalPages   = max(1, (int)ceil($totalRecords / $perPage));

        $rowsPage = $this->repo->fetchPage($perPage, $offset, 'id', 'ASC');

        $msg = $_GET['msg'] ?? '';
        $activeTab = 'upload';

        render('pages/index.view.php', compact(
            'perPage', 'page', 'offset',
            'totalRecords', 'totalPages',
            'rowsPage', 'msg', 'activeTab'
        ));
    }

    /**
     * 指標頁：顯示整體平均 PSNR/SSIM/L1
     * GET /metrics
     * - 讀取總筆數與平均值
     * - 交由 view 顯示
     */

    public function metrics(): void
    {
        $totalRecords = $this->repo->countAll();
        $avgAll = $this->repo->avgAll();

        $activeTab = 'metrics';
        render('pages/metrics.view.php', compact('totalRecords', 'avgAll', 'activeTab'));
    }

    /**
     * 上傳動作：交由 Pipeline 完成「存檔 + Docker 增亮 + 計算指標 + DB 寫入」
     * POST /upload
     * - 成功：導回首頁並顯示 msg=ok
     * - 失敗：導回首頁並顯示 msg=upload_invalid
     */

    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }

        try {
            $this->pipeline->handleUpload($_FILES['image'] ?? []);
            redirect('index.php?msg=ok');
        } catch (\Throwable) {
            redirect('index.php?msg=upload_invalid');
        }
    }

    /**
     * 刪除動作：刪檔（uploads/outputs）+ 刪 DB 紀錄
     * POST /delete
     * - 先查 stored_name，以便定位實體檔案
     * - 檔案刪除完成後才刪 DB
     */

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
            redirect('index.php?msg=delete_invalid');
        }

        $id = (int)$_POST['id'];
        $storedName = $this->repo->findStoredNameById($id);

        if (!$storedName) {
            redirect('index.php?msg=notfound');
        }

        $paths = [
            PUBLIC_ROOT . '/uploads/' . $storedName,
            PUBLIC_ROOT . '/outputs/' . $storedName,
        ];

        foreach ($paths as $p) {
            if (is_file($p)) @unlink($p);
        }

        $this->repo->deleteById($id);
        redirect('index.php?msg=deleted');
    }

    /**
     * 匯出資料庫紀錄 CSV
     * GET /export
     */

    public function exportCsv(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="lowlight_records_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['id','orig_name','stored_name','created_at','psnr','ssim','l1']);

        foreach ($this->repo->fetchAllAsc() as $row) {
            fputcsv($out, [
                $row['id'], $row['orig_name'], $row['stored_name'], $row['created_at'],
                $row['psnr'], $row['ssim'], $row['l1']
            ]);
        }

        fclose($out);
        exit;
    }
}
