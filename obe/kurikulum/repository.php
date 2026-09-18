<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class KurikulumRepository extends BaseRepository
{
    protected string $table = 'obe_kurikulum';

    public function getAll(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_kurikulum
            WHERE unit_id = ?
            ORDER BY tahun DESC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_kurikulum WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_kurikulum (unit_id, tahun, nama, is_active, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iisi", $data['unit_id'], $data['tahun'], $data['nama'], $data['is_active']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_kurikulum
            SET tahun = ?, nama = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isii", $data['tahun'], $data['nama'], $data['is_active'], $id);
        $this->execute($stmt);

        return true;
    }

    public function countMataKuliah(int $kurikulumId): int
    {
        $stmt = $this->prepare("SELECT COUNT(*) AS total FROM obe_mata_kuliah WHERE kurikulum_id = ? AND is_active = 1");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int) ($row['total'] ?? 0);
    }
}