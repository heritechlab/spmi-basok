<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class KetercapaianMahasiswaRepository extends BaseRepository
{
    protected string $table = 'obe_mahasiswa';

    public function getMahasiswaListByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT id, nim, nama
            FROM obe_mahasiswa
            WHERE kurikulum_id = ? AND is_active = 1
            ORDER BY nama ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMataKuliahListByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT id, code, name, semester
            FROM obe_mata_kuliah
            WHERE kurikulum_id = ? AND is_active = 1
            ORDER BY semester ASC, name ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
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

    public function getBobotKontribusiByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT b.mata_kuliah_id, b.cpl_id, b.bobot_persen
            FROM obe_bobot_kontribusi_mk_cpl b
            JOIN obe_mata_kuliah mk ON mk.id = b.mata_kuliah_id
            WHERE mk.kurikulum_id = ?
        ");
        $stmt->bind_param("i", $kurikulumId);
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

    public function getNilaiByMahasiswaAndMataKuliah(int $mahasiswaId, int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT p.rps_id, p.rencana_evaluasi_id, p.nilai
            FROM obe_penilaian p
            JOIN obe_rps r ON r.id = p.rps_id
            WHERE r.mata_kuliah_id = ? AND p.mahasiswa_id = ? AND p.periode_id = ?
        ");
        $stmt->bind_param("iii", $mataKuliahId, $mahasiswaId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getDetailNilaiPerTm(int $mahasiswaId, int $mataKuliahId): array
    {
        $stmt = $this->prepare("
            SELECT r.pertemuan, sc.code AS sub_cpmk_code, c.code AS cpmk_code, cl.code AS cpl_code,
                   r.bobot_penilaian AS rps_bobot_total,
                   m.rencana_evaluasi_id, m.bobot_persen AS komponen_bobot, re.basis_evaluasi,
                   p.nilai
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            LEFT JOIN obe_rps_rencana_evaluasi_map m ON m.rps_id = r.id
            LEFT JOIN obe_rencana_evaluasi re ON re.id = m.rencana_evaluasi_id
            LEFT JOIN obe_penilaian p ON p.rps_id = r.id
                 AND p.rencana_evaluasi_id = COALESCE(m.rencana_evaluasi_id, 0)
                 AND p.mahasiswa_id = ?
            WHERE r.mata_kuliah_id = ?
            ORDER BY r.pertemuan ASC, re.basis_evaluasi ASC
        ");
        $stmt->bind_param("ii", $mahasiswaId, $mataKuliahId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}