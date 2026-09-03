<?php

declare(strict_types=1);

namespace App\services;

use App\models\ImageModel;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class EnhancePipeline
{
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
    private const MAX_PIXELS = 4_000_000;
    private const MIME_EXTENSIONS = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function __construct(private ImageModel $repo, private DockerCli $docker) {}

    public function handleUpload(array $file): string
    {
        [$origName, $tmpName, $extension] = $this->validateUpload($file);
        $this->ensureStorageDirectories();

        $storedName = $this->createStoredName($extension);
        $origFsPath = PUBLIC_ROOT . '/uploads/' . $storedName;
        $enhFsPath = PUBLIC_ROOT . '/outputs/' . $storedName;
        $lock = null;

        try {
            $lock = fopen(sys_get_temp_dir() . '/lowlight-demo-processing.lock', 'c');
            if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('processing_busy');
            }
            if (!move_uploaded_file($tmpName, $origFsPath)) {
                throw new RuntimeException('upload_move_failed');
            }

            $started = microtime(true);
            $result = $this->docker->runEnhance(
                PROJECT_ROOT,
                '/workspace/public/uploads/' . $storedName,
                '/workspace/public/outputs/' . $storedName
            );
            $processingMs = (int)round((microtime(true) - $started) * 1000);

            if (!$result['ok'] || !is_file($enhFsPath)) {
                error_log('Lowlight Docker failure: ' . json_encode($result, JSON_UNESCAPED_SLASHES));
                throw new RuntimeException($result['timed_out'] ? 'processing_timeout' : 'processing_failed');
            }

            $payload = json_decode($result['stdout'], true);
            if (!is_array($payload) || empty($payload['ok']) || !is_array($payload['analysis'] ?? null)) {
                error_log('Lowlight invalid Docker response: ' . $result['stdout']);
                throw new RuntimeException('analysis_failed');
            }

            $this->repo->insertRecord($origName, $storedName, $payload['analysis'], $processingMs);
            return $storedName;
        } catch (Throwable $exception) {
            if (is_file($origFsPath)) {
                unlink($origFsPath);
            }
            if (is_file($enhFsPath)) {
                unlink($enhFsPath);
            }
            throw $exception;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    /** @return array{string, string, string} */
    private function validateUpload(array $file): array
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('upload_too_large');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('upload_invalid');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        $origName = trim((string)($file['name'] ?? 'image'));
        $size = (int)($file['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_UPLOAD_BYTES || !is_uploaded_file($tmpName)) {
            throw new RuntimeException($size > self::MAX_UPLOAD_BYTES ? 'upload_too_large' : 'upload_invalid');
        }

        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false || $imageInfo[0] < 1 || $imageInfo[1] < 1) {
            throw new RuntimeException('upload_invalid');
        }
        if ($imageInfo[0] * $imageInfo[1] > self::MAX_PIXELS) {
            throw new RuntimeException('upload_dimensions');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName);
        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
            throw new RuntimeException('upload_type');
        }

        if ($origName === '') {
            $origName = 'image.' . self::MIME_EXTENSIONS[$mime];
        }
        $displayName = function_exists('mb_substr')
            ? mb_substr($origName, 0, 255, 'UTF-8')
            : substr($origName, 0, 255);

        return [$displayName, $tmpName, self::MIME_EXTENSIONS[$mime]];
    }

    private function ensureStorageDirectories(): void
    {
        foreach ([PUBLIC_ROOT . '/uploads', PUBLIC_ROOT . '/outputs'] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                throw new RuntimeException('storage_unavailable');
            }
        }
    }

    private function createStoredName(string $extension): string
    {
        do {
            $name = 'll_' . (new DateTimeImmutable())->format('Ymd_His_v') . '.' . $extension;
            $exists = is_file(PUBLIC_ROOT . '/uploads/' . $name)
                || is_file(PUBLIC_ROOT . '/outputs/' . $name);
            if ($exists) {
                usleep(1000);
            }
        } while ($exists);

        return $name;
    }
}
