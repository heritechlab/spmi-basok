<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class SubCpmkRepository extends BaseRepository
{
    protected string $table = 'obe_sub_cpmk';

    public function getAllByUnit(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT sc.*, c.code AS cpmk_code, c.mata_kuliah_id, mk.name AS mk_name, mk.semester AS mk_semester
            FROM obe_sub_cpmk sc
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            JOIN obe_mata_kuliah mk ON mk.id = c.mata_kuliah_id
            WHERE mk.unit_id = ? AND mk.is_active = 1
            ORDER BY mk.semester ASC, mk.name ASC, c.sort_order ASC, sc.sort_order ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_sub_cpmk WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getTotalBobotByCpmk(int $cpmkId, int $excludeId = 0): float
    {
        $sql = "SELECT SUM(bobot) AS total FROM obe_sub_cpmk WHERE cpmk_id = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("ii", $cpmkId, $excludeId);
        } else {
            $stmt->bind_param("i", $cpmkId);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (float) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_sub_cpmk (cpmk_id, code, description, bobot, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issdi", $data['cpmk_id'], $data['code'], $data['description'], $data['bobot'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_sub_cpmk
            SET code = ?, description = ?, bobot = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssdii", $data['code'], $data['description'], $data['bobot'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_sub_cpmk WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}