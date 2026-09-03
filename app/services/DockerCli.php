<?php

declare(strict_types=1);

namespace App\services;

final class DockerCli
{
    private const TIMEOUT_SECONDS = 120;

    public function __construct(private string $imageName) {}

    /** @return array{ok: bool, exit_code: int, stdout: string, stderr: string, timed_out: bool} */
    public function runEnhance(string $projectRootAbs, string $cliOrig, string $cliEnh): array
    {
        $projectRootAbs = realpath($projectRootAbs) ?: $projectRootAbs;
        $containerName = 'lowlight-' . bin2hex(random_bytes(6));
        $command = [
            'docker', 'run', '--rm', '--name', $containerName,
            '--network', 'none', '--read-only', '--cap-drop', 'ALL',
            '--security-opt', 'no-new-privileges', '--memory', '1g',
            '--cpus', '2', '--pids-limit', '128',
            '--tmpfs', '/tmp:rw,noexec,nosuid,size=64m',
            '-e', 'PYTHONDONTWRITEBYTECODE=1', '-e', 'TORCH_NUM_THREADS=2',
            '-v', $this->mount($projectRootAbs . '/python_api', '/workspace/python_api', true),
            '-v', $this->mount($projectRootAbs . '/public/uploads', '/workspace/public/uploads', true),
            '-v', $this->mount($projectRootAbs . '/public/outputs', '/workspace/public/outputs'),
            $this->imageName,
            'python', '/workspace/python_api/enhance_cli.py', $cliOrig, $cliEnh,
        ];

        return $this->run($command, $containerName);
    }

    private function mount(string $hostPath, string $containerPath, bool $readOnly = false): string
    {
        $hostPath = str_replace('\\', '/', realpath($hostPath) ?: $hostPath);
        return $hostPath . ':' . $containerPath . ($readOnly ? ':ro' : '');
    }

    /**
     * @param list<string> $command
     * @return array{ok: bool, exit_code: int, stdout: string, stderr: string, timed_out: bool}
     */
    private function run(array $command, string $containerName): array
    {
        if (!function_exists('proc_open')) {
            return $this->failure('proc_open is unavailable');
        }

        $pipes = [];
        $process = @proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            return $this->failure('Unable to start Docker');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);
        $stdout = '';
        $stderr = '';
        $timedOut = false;
        $started = microtime(true);

        while (true) {
            $stdout .= (string)stream_get_contents($pipes[1]);
            $stderr .= (string)stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = (int)$status['exitcode'];
                break;
            }
            if (microtime(true) - $started > self::TIMEOUT_SECONDS) {
                $timedOut = true;
                proc_terminate($process);
                $this->removeContainer($containerName);
                $exitCode = 124;
                break;
            }
            usleep(50000);
        }

        $stdout .= (string)stream_get_contents($pipes[1]);
        $stderr .= (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $closedCode = proc_close($process);
        if (!$timedOut && $exitCode < 0 && $closedCode >= 0) {
            $exitCode = $closedCode;
        }

        return [
            'ok' => !$timedOut && $exitCode === 0,
            'exit_code' => $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
            'timed_out' => $timedOut,
        ];
    }

    /** @return array{ok: bool, exit_code: int, stdout: string, stderr: string, timed_out: bool} */
    private function failure(string $message): array
    {
        return ['ok' => false, 'exit_code' => 1, 'stdout' => '', 'stderr' => $message, 'timed_out' => false];
    }

    private function removeContainer(string $containerName): void
    {
        $pipes = [];
        $process = @proc_open(['docker', 'rm', '-f', $containerName], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            return;
        }
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        proc_close($process);
    }
}
