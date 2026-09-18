<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class LaporanCplRepository extends BaseRepository
{
    protected string $table = 'obe_mata_kuliah';

    public function getMataKuliahListByUnit(int $unitId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT DISTINCT mk.id, mk.code, mk.name, mk.semester, mk.kurikulum_id
            FROM obe_mata_kuliah mk
            JOIN obe_kurikulum k ON k.id = mk.kurikulum_id
            JOIN obe_rps r ON r.mata_kuliah_id = mk.id AND r.periode_id = ?
            WHERE k.unit_id = ? AND mk.is_active = 1
            ORDER BY mk.semester ASC, mk.name ASC
        ");
        $stmt->bind_param("ii", $periodeId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getCplListByUnit(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT id, code
            FROM obe_cpl
            WHERE unit_id = ? AND is_active = 1
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getBobotKontribusiByUnit(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT b.mata_kuliah_id, b.cpl_id, b.bobot_persen
            FROM obe_bobot_kontribusi_mk_cpl b
            JOIN obe_mata_kuliah mk ON mk.id = b.mata_kuliah_id
            JOIN obe_kurikulum k ON k.id = mk.kurikulum_id
            WHERE k.unit_id = ?
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getRpsWithCplByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.id AS rps_id, r.bobot_penilaian AS rps_bobot_total,
                   cl.id AS cpl_id,
                   m.rencana_evaluasi_id, m.bobot_persen AS komponen_bobot
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            LEFT JOIN obe_rps_rencana_evaluasi_map m ON m.rps_id = r.id
            WHERE r.mata_kuliah_id = ? AND r.periode_id = ?
        ");
        $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMahasiswaListByUnit(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT m.id
            FROM obe_mahasiswa m
            JOIN obe_kurikulum k ON k.id = m.kurikulum_id
            WHERE k.unit_id = ? AND m.is_active = 1 AND m.status = 'Aktif'
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getNilaiByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT p.rps_id, p.rencana_evaluasi_id, p.mahasiswa_id, p.nilai
            FROM obe_penilaian p
            JOIN obe_rps r ON r.id = p.rps_id
            WHERE r.mata_kuliah_id = ? AND p.periode_id = ?
        ");
        $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function getPeriodeInfo(int $periodeId): ?array
    {
        $stmt = $this->prepare("SELECT tahun_ajaran, jenis_semester FROM obe_periode_akademik WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $periodeId);
        $this->execute($stmt);

        return $this->fetchOne($stmt);
    }
}