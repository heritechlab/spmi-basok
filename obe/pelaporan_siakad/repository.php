<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class PelaporanSiakadRepository extends BaseRepository
{
    protected string $table = 'obe_mata_kuliah';

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

    public function getRpsWithKomponenByMataKuliah(int $mataKuliahId): array
    {
        $stmt = $this->prepare("
            SELECT r.id AS rps_id, r.bobot_penilaian AS rps_bobot_total,
                   m.rencana_evaluasi_id, m.bobot_persen AS komponen_bobot,
                   re.komponen_siakad
            FROM obe_rps r
            LEFT JOIN obe_rps_rencana_evaluasi_map m ON m.rps_id = r.id
            LEFT JOIN obe_rencana_evaluasi re ON re.id = m.rencana_evaluasi_id
            WHERE r.mata_kuliah_id = ?
        ");
        $stmt->bind_param("i", $mataKuliahId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getNilaiByMataKuliah(int $mataKuliahId): array
    {
        $stmt = $this->prepare("
            SELECT p.rps_id, p.rencana_evaluasi_id, p.mahasiswa_id, p.nilai
            FROM obe_penilaian p
            JOIN obe_rps r ON r.id = p.rps_id
            WHERE r.mata_kuliah_id = ?
        ");
        $stmt->bind_param("i", $mataKuliahId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}