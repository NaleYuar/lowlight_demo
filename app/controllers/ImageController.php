<?php

declare(strict_types=1);

namespace App\controllers;

use App\models\ImageModel;
use App\services\EnhancePipeline;
use App\services\SpreadsheetExporter;
use RuntimeException;
use Throwable;

final class ImageController
{
    public function __construct(
        private ImageModel $repo,
        private EnhancePipeline $pipeline,
        private SpreadsheetExporter $spreadsheetExporter
    ) {}

    public function index(): void
    {
        $perPage = 6;
        $totalRecords = $this->repo->countAll();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));
        $page = min($totalPages, max(1, (int)($_GET['page'] ?? 1)));
        $offset = ($page - 1) * $perPage;
        $rowsPage = $this->repo->fetchPage($perPage, $offset);
        $flash = pull_flash();
        render('pages/index.view.php', compact(
            'perPage', 'page', 'offset', 'totalRecords', 'totalPages',
            'rowsPage', 'flash'
        ));
    }

    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
            set_flash('error', '請求已失效，請重新選擇圖片後再試一次。');
            redirect('index.php');
        }

        try {
            $this->pipeline->handleUpload($_FILES['image'] ?? []);
            set_flash('success', '圖片增亮完成。');
        } catch (RuntimeException $exception) {
            set_flash('error', $this->friendlyPipelineError($exception->getMessage()));
        } catch (Throwable $exception) {
            error_log('Lowlight upload exception: ' . $exception::class . ': ' . $exception->getMessage());
            set_flash('error', '處理失敗，請確認 Docker 與模型服務是否正常。');
        }

        redirect('index.php');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
            set_flash('error', '刪除請求已失效，請重新操作。');
            redirect('index.php');
        }

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $record = is_int($id) ? $this->repo->findById($id) : null;
        if ($record === null) {
            set_flash('error', '找不到要刪除的紀錄。');
            redirect('index.php');
        }

        $storedName = (string)$record['stored_name'];
        if (basename($storedName) !== $storedName) {
            set_flash('error', '紀錄中的檔案路徑不安全，已停止刪除。');
            redirect('index.php');
        }

        $moved = [];
        try {
            foreach (['uploads', 'outputs'] as $folder) {
                $path = PUBLIC_ROOT . '/' . $folder . '/' . $storedName;
                if (!is_file($path)) {
                    continue;
                }
                $quarantine = $path . '.deleting-' . bin2hex(random_bytes(4));
                if (!rename($path, $quarantine)) {
                    throw new RuntimeException('file_move_failed');
                }
                $moved[$path] = $quarantine;
            }

            if (!$this->repo->deleteById($id)) {
                throw new RuntimeException('record_delete_failed');
            }
            foreach ($moved as $quarantine) {
                if (is_file($quarantine)) {
                    unlink($quarantine);
                }
            }
            set_flash('success', '紀錄與對應影像已刪除。');
        } catch (Throwable $exception) {
            foreach ($moved as $original => $quarantine) {
                if (is_file($quarantine)) {
                    rename($quarantine, $original);
                }
            }
            error_log('Lowlight delete exception: ' . $exception->getMessage());
            set_flash('error', '刪除失敗，檔案已盡可能還原。');
        }

        redirect('index.php');
    }

    public function exportSpreadsheet(): void
    {
        $this->spreadsheetExporter->download($this->repo->fetchAllAsc());
    }

    private function friendlyPipelineError(string $code): string
    {
        return match ($code) {
            'upload_too_large' => '圖片超過 10 MB，請壓縮後再試。',
            'upload_dimensions' => '圖片像素過大，上限為 400 萬像素（約 2,000 × 2,000）。',
            'upload_type' => '只接受有效的 JPG 或 PNG 圖片。',
            'processing_busy' => '目前有另一張圖片正在處理，請稍後再試。',
            'processing_timeout' => '模型處理超過 120 秒，已安全停止。',
            'processing_failed', 'analysis_failed' => '增亮失敗，請確認 Docker 與模型是否正常。',
            'storage_unavailable' => '影像儲存空間目前無法使用。',
            default => '圖片無效或上傳未完成，請改用 JPG / PNG 再試。',
        };
    }
}
