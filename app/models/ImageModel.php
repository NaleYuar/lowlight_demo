<?php

declare(strict_types=1);

namespace App\models;

use PDO;

final class ImageModel
{
    public function __construct(private PDO $pdo) {}

    public function countAll(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM images')->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchPage(int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM images ORDER BY id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM images WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM images WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() === 1;
    }

    public function insertRecord(string $origName, string $storedName, array $analysis, int $processingMs): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO images (
                original_name, stored_name,
                brightness_before_pct, brightness_after_pct,
                contrast_before_pct, contrast_after_pct,
                image_width_px, image_height_px,
                original_size_kb, enhanced_size_kb,
                processing_ms
            ) VALUES (
                :original_name, :stored_name,
                :brightness_before_pct, :brightness_after_pct,
                :contrast_before_pct, :contrast_after_pct,
                :image_width_px, :image_height_px,
                :original_size_kb, :enhanced_size_kb,
                :processing_ms
            )
        ');
        $stmt->execute([
            ':original_name' => $origName,
            ':stored_name' => $storedName,
            ':brightness_before_pct' => $analysis['brightness_before_pct'] ?? null,
            ':brightness_after_pct' => $analysis['brightness_after_pct'] ?? null,
            ':contrast_before_pct' => $analysis['contrast_before_pct'] ?? null,
            ':contrast_after_pct' => $analysis['contrast_after_pct'] ?? null,
            ':image_width_px' => $analysis['image_width_px'] ?? null,
            ':image_height_px' => $analysis['image_height_px'] ?? null,
            ':original_size_kb' => $analysis['original_size_kb'] ?? null,
            ':enhanced_size_kb' => $analysis['enhanced_size_kb'] ?? null,
            ':processing_ms' => $processingMs,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function fetchAllAsc(): array
    {
        return $this->pdo->query('SELECT * FROM images ORDER BY id ASC')->fetchAll();
    }
}
