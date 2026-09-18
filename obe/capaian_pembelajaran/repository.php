<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class CapaianPembelajaranRepository extends BaseRepository
{
    protected string $table = 'obe_capaian_pembelajaran';

    public function getAllByMataKuliah(int $mataKuliahId): array
    {
        $stmt = $this->prepare("
            SELECT cp.*, c.code AS cpmk_code, sc.code AS sub_cpmk_code, sc.bobot AS sub_cpmk_bobot
            FROM obe_capaian_pembelajaran cp
            JOIN obe_cpmk c ON c.id = cp.cpmk_id
            LEFT JOIN obe_sub_cpmk sc ON sc.id = cp.sub_cpmk_id
            WHERE cp.mata_kuliah_id = ?
            ORDER BY cp.pertemuan ASC, cp.sort_order ASC
        ");
        $stmt->bind_param("i", $mataKuliahId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_capaian_pembelajaran WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_capaian_pembelajaran
                (mata_kuliah_id, pertemuan, cpmk_id, sub_cpmk_id, indikator_penilaian, bentuk_evaluasi, bobot_per_evaluasi, bobot_penilaian, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iiiissddi",
            $data['mata_kuliah_id'], $data['pertemuan'], $data['cpmk_id'], $data['sub_cpmk_id'],
            $data['indikator_penilaian'], $data['bentuk_evaluasi'],
            $data['bobot_per_evaluasi'], $data['bobot_penilaian'], $data['sort_order']
        );
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_capaian_pembelajaran
            SET pertemuan = ?, cpmk_id = ?, sub_cpmk_id = ?, indikator_penilaian = ?, bentuk_evaluasi = ?,
                bobot_per_evaluasi = ?, bobot_penilaian = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iiissddii",
            $data['pertemuan'], $data['cpmk_id'], $data['sub_cpmk_id'], $data['indikator_penilaian'], $data['bentuk_evaluasi'],
            $data['bobot_per_evaluasi'], $data['bobot_penilaian'], $data['sort_order'], $id
        );
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_capaian_pembelajaran WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function getTotalBobotPenilaian(int $mataKuliahId, int $excludeId = 0): float
    {
        $sql = "SELECT SUM(bobot_penilaian) AS total FROM obe_capaian_pembelajaran WHERE mata_kuliah_id = ?";

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

        public function getSubCpmkByCpmk(int $cpmkId): array
    {
        $stmt = $this->prepare("
            SELECT id, code, description, bobot
            FROM obe_sub_cpmk
            WHERE cpmk_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("i", $cpmkId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}