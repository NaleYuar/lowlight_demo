<?php

/**
 * - 負責「上傳檔案 → 存到 public/uploads → docker 執行 enhance_cli → docker 執行 metrics_cli → 回傳結果」
 */

declare(strict_types=1);

namespace app\services;

use App\models\ImageModel;

final class EnhancePipeline
{
    public function __construct(
        private ImageModel $repo,
        private DockerCli $docker
    ) {}

    public function handleUpload(array $file): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('upload_invalid');
        }

        $origName = (string)$file['name'];
        $tmpName  = (string)$file['tmp_name'];

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            throw new \RuntimeException('upload_invalid');
        }

        if (!is_dir(PUBLIC_ROOT . '/uploads')) mkdir(PUBLIC_ROOT . '/uploads', 0777, true);
        if (!is_dir(PUBLIC_ROOT . '/outputs')) mkdir(PUBLIC_ROOT . '/outputs', 0777, true);

        $basename  = pathinfo($origName, PATHINFO_FILENAME);
        $timestamp = date('Ymd_His');
        $safeBase  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $basename);
        $storedName = $safeBase . '_' . $timestamp . '.' . $ext;

        $origFsPath = PUBLIC_ROOT . '/uploads/' . $storedName;
        $enhFsPath  = PUBLIC_ROOT . '/outputs/' . $storedName;

        if (!move_uploaded_file($tmpName, $origFsPath)) {
            throw new \RuntimeException('upload_invalid');
        }

        // container 內固定用 /workspace
        $cliOrig = '/workspace/public/uploads/' . $storedName;
        $cliEnh  = '/workspace/public/outputs/' . $storedName;

        $enhOut = $this->docker->runEnhance(PROJECT_ROOT, $cliOrig, $cliEnh);

        if (!is_file($enhFsPath)) {
            @unlink($origFsPath);
            file_put_contents(PROJECT_ROOT . '/debug_docker.log', $enhOut . PHP_EOL, FILE_APPEND);
            throw new \RuntimeException('upload_invalid');
        }

        $metricsOut = $this->docker->runMetrics(PROJECT_ROOT, $cliOrig, $cliEnh);

        $psnr = $ssim = $l1 = null;
        if ($metricsOut !== '') {
            $m = json_decode($metricsOut, true);
            if (is_array($m) && empty($m['error'])) {
                $psnr = isset($m['psnr']) ? (float)$m['psnr'] : null;
                $ssim = isset($m['ssim']) ? (float)$m['ssim'] : null;
                $l1   = isset($m['l1'])   ? (float)$m['l1']   : null;
            }
        }

        $this->repo->insertRecord($origName, $storedName, $psnr, $ssim, $l1);

        return $storedName;
    }
}
