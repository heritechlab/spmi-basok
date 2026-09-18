<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class BahanKajianRepository extends BaseRepository
{
    protected string $table = 'obe_bahan_kajian';

    public function getAllByUnit(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT bk.*, sc.code AS sub_cpmk_code, c.code AS cpmk_code, c.mata_kuliah_id, mk.name AS mk_name, mk.semester AS mk_semester
            FROM obe_bahan_kajian bk
            JOIN obe_sub_cpmk sc ON sc.id = bk.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            JOIN obe_mata_kuliah mk ON mk.id = c.mata_kuliah_id
            WHERE mk.unit_id = ? AND mk.is_active = 1
            ORDER BY mk.semester ASC, mk.name ASC, c.sort_order ASC, sc.sort_order ASC, bk.sort_order ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_bahan_kajian WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_bahan_kajian (sub_cpmk_id, nama_bahan_kajian, deskripsi, sort_order, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issi", $data['sub_cpmk_id'], $data['nama_bahan_kajian'], $data['deskripsi'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_bahan_kajian
            SET nama_bahan_kajian = ?, deskripsi = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $data['nama_bahan_kajian'], $data['deskripsi'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_bahan_kajian WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
    
    public function getSubCpmkList(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT sc.id, sc.code, c.code AS cpmk_code, c.mata_kuliah_id, mk.name AS mk_name, mk.semester AS mk_semester
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
}