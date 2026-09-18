<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class CpmkRepository extends BaseRepository
{
    protected string $table = 'obe_cpmk';

    public function getAllByMataKuliah(int $mataKuliahId): array
    {
        $stmt = $this->prepare("
            SELECT c.*, cl.code AS cpl_code
            FROM obe_cpmk c
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            WHERE c.mata_kuliah_id = ?
            ORDER BY c.sort_order ASC, c.code ASC
        ");
        $stmt->bind_param("i", $mataKuliahId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

        public function getAllByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT c.*, cl.code AS cpl_code, mk.name AS mk_name, mk.semester AS mk_semester
            FROM obe_cpmk c
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            JOIN obe_mata_kuliah mk ON mk.id = c.mata_kuliah_id
            WHERE mk.kurikulum_id = ? AND mk.is_active = 1
            ORDER BY mk.semester ASC, mk.name ASC, c.sort_order ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

        public function getMatriksCpmkCpl(int $unitId, int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT c.id, c.code, c.cpl_id, c.mata_kuliah_id, mk.name AS mk_name, mk.semester AS mk_semester
            FROM obe_cpmk c
            JOIN obe_mata_kuliah mk ON mk.id = c.mata_kuliah_id
            WHERE mk.kurikulum_id = ? AND mk.is_active = 1
            ORDER BY mk.semester ASC, mk.name ASC, c.sort_order ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);
        $cpmkList = $this->fetchAll($stmt);

        $stmtCpl = $this->prepare("SELECT id, code FROM obe_cpl WHERE unit_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmtCpl->bind_param("i", $unitId);
        $this->execute($stmtCpl);
        $cplList = $this->fetchAll($stmtCpl);

        $mapSet = [];
        foreach ($cpmkList as $c) {
            if ($c['cpl_id']) {
                $mapSet[$c['id'] . '_' . $c['cpl_id']] = true;
            }
        }

        return [
            'cpmk' => $cpmkList,
            'cpl'  => $cplList,
            'map'  => $mapSet,
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_cpmk WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_cpmk (mata_kuliah_id, cpl_id, code, description, bobot, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissdi", $data['mata_kuliah_id'], $data['cpl_id'], $data['code'], $data['description'], $data['bobot'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_cpmk
            SET cpl_id = ?, code = ?, description = ?, bobot = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("issdii", $data['cpl_id'], $data['code'], $data['description'], $data['bobot'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function getTotalBobotByMataKuliah(int $mataKuliahId, int $excludeId = 0): float
    {
        $sql = "SELECT SUM(bobot) AS total FROM obe_cpmk WHERE mata_kuliah_id = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("ii", $mataKuliahId, $excludeId);
        } else {
            $stmt->bind_param("i", $mataKuliahId);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (float) ($row['total'] ?? 0);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_cpmk WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}