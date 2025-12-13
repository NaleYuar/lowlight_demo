<?php

/**
 * DockerCli
 * - Docker 指令執行器（Infrastructure Adapter）
 * - 負責組裝並執行 docker run，並處理 Windows / Linux 的路徑與 quoting 差異
 * - 上層（Pipeline/Controller）只需要提供：專案根目錄、容器內檔案路徑、要跑的 python script
 */

declare(strict_types=1);

namespace app\services;

final class DockerCli
{
    public function __construct(private string $imageName) {}

    /**
     * 呼叫容器執行 enhance_cli.py
     * - 用途：產生增亮輸出圖（寫入 public/outputs）
     * - 回傳：docker stdout/stderr（可拿去 debug log）
     */

    public function runEnhance(string $projectRootAbs, string $cliOrig, string $cliEnh): string
    {
        $cmd = $this->buildCmd($projectRootAbs, 'enhance_cli.py', $cliOrig, $cliEnh);
        return (string)shell_exec($cmd);
    }

    /**
     * - 用途：計算 PSNR/SSIM/L1
     * - 回傳：docker stdout/stderr（成功時為 JSON；失敗時可能是錯誤文字）
     */

    public function runMetrics(string $projectRootAbs, string $cliOrig, string $cliEnh): string
    {
        $cmd = $this->buildCmd($projectRootAbs, 'metrics_cli.py', $cliOrig, $cliEnh);
        return (string)shell_exec($cmd);
    }

    /**
     * 組裝 docker run 指令（同時處理 Windows / Linux 差異）
     * - Windows：docker -v 不使用 escapeshellarg；volume 路徑需轉成 D:/... 形式
     * - Linux：可安全使用 escapeshellarg；bash -lc 外層用單引號包住命令
     *
     * @param string $projectRootAbs Host 上專案根目錄（用於掛載到 /workspace）
     * @param string $script Python 腳本（enhance_cli.py 或 metrics_cli.py）
     * @param string $cliOrig 容器內原圖路徑（例如 /workspace/public/uploads/xxx.png）
     * @param string $cliEnh  容器內輸出路徑（例如 /workspace/public/outputs/xxx.png）
     */

    private function buildCmd(string $projectRootAbs, string $script, string $cliOrig, string $cliEnh): string
    {
        $projectRootAbs = realpath($projectRootAbs) ?: $projectRootAbs;
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $volumeRoot = str_replace('\\', '/', $projectRootAbs);
            $volumeOption = $volumeRoot . ':/workspace';

            return 'docker run --rm -v ' . $volumeOption
                . ' ' . $this->imageName
                . ' bash -lc "cd /workspace/python_api && python ' . $script
                . ' ' . $cliOrig . ' ' . $cliEnh . ' 2>&1"';
        }

        $volumeOption = escapeshellarg($projectRootAbs) . ':/workspace';

        return sprintf(
            'docker run --rm -v %s %s bash -lc ' .
            '\'cd /workspace/python_api && python %s "%s" "%s" 2>&1\'',
            $volumeOption,
            escapeshellarg($this->imageName),
            $script,
            $cliOrig,
            $cliEnh
        );
    }
}
