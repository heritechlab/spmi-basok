<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class PenilaianRepository extends BaseRepository
{
    protected string $table = 'obe_penilaian';

    public function getKomponenListByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.id AS rps_id, r.pertemuan, r.bobot_penilaian AS rps_bobot_total,
                   sc.id AS sub_cpmk_id, sc.code AS sub_cpmk_code, c.id AS cpmk_id, c.code AS cpmk_code,
                   c.cpl_id, cl.code AS cpl_code,
                   m.rencana_evaluasi_id, m.bobot_persen AS komponen_bobot,
                   re.basis_evaluasi
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            LEFT JOIN obe_rps_rencana_evaluasi_map m ON m.rps_id = r.id
            LEFT JOIN obe_rencana_evaluasi re ON re.id = m.rencana_evaluasi_id
            WHERE r.mata_kuliah_id = ? AND r.periode_id = ?
            ORDER BY r.pertemuan ASC, re.basis_evaluasi ASC
        ");
        $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getKomponenWithCplByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.id AS rps_id, r.bobot_penilaian AS rps_bobot_total,
                   cl.id AS cpl_id, cl.code AS cpl_code,
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

    public function getMahasiswaListByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT id, nim, nama
            FROM obe_mahasiswa
            WHERE kurikulum_id = ? AND is_active = 1 AND status = 'Aktif'
            ORDER BY nama ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getNilaiGrid(int $mataKuliahId, int $periodeId): array
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

    public function saveNilai(int $rpsId, int $periodeId, int $rencanaEvaluasiId, int $mahasiswaId, float $nilai): bool
    {
        $stmt = $this->prepare("
            INSERT INTO obe_penilaian (rps_id, periode_id, rencana_evaluasi_id, mahasiswa_id, nilai)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)
        ");
        $stmt->bind_param("iiiid", $rpsId, $periodeId, $rencanaEvaluasiId, $mahasiswaId, $nilai);
        $this->execute($stmt);

        return true;
    }
}