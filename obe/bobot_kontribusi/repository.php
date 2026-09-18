<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class BobotKontribusiRepository extends BaseRepository
{
    protected string $table = 'obe_bobot_kontribusi_mk_cpl';

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

    public function getBobotByKurikulum(int $kurikulumId): array
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

    public function saveBobot(int $mataKuliahId, int $cplId, float $bobot): bool
    {
        $stmt = $this->prepare("
            INSERT INTO obe_bobot_kontribusi_mk_cpl (mata_kuliah_id, cpl_id, bobot_persen)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE bobot_persen = VALUES(bobot_persen)
        ");
        $stmt->bind_param("iid", $mataKuliahId, $cplId, $bobot);
        $this->execute($stmt);

        return true;
    }

    public function getTingkat1ByKurikulum(int $kurikulumId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.mata_kuliah_id, cl.id AS cpl_id, SUM(r.bobot_penilaian) AS total_bobot
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            JOIN obe_cpl cl ON cl.id = c.cpl_id
            JOIN obe_mata_kuliah mk ON mk.id = r.mata_kuliah_id
            WHERE mk.kurikulum_id = ? AND r.periode_id = ?
            GROUP BY r.mata_kuliah_id, cl.id
        ");
        $stmt->bind_param("ii", $kurikulumId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

        public function getTingkat1DetailByKurikulum(int $kurikulumId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.mata_kuliah_id, mk.name AS mk_name, mk.code AS mk_code,
                   cl.id AS cpl_id, cl.code AS cpl_code,
                   SUM(r.bobot_penilaian) AS total_bobot
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            JOIN obe_cpl cl ON cl.id = c.cpl_id
            JOIN obe_mata_kuliah mk ON mk.id = r.mata_kuliah_id
            WHERE mk.kurikulum_id = ? AND r.periode_id = ?
            GROUP BY r.mata_kuliah_id, cl.id
            ORDER BY cl.sort_order ASC, mk.semester ASC, mk.name ASC
        ");
        $stmt->bind_param("ii", $kurikulumId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

        public function deleteAllByKurikulum(int $kurikulumId): void
    {
        $stmt = $this->prepare("
            DELETE b FROM obe_bobot_kontribusi_mk_cpl b
            JOIN obe_mata_kuliah mk ON mk.id = b.mata_kuliah_id
            WHERE mk.kurikulum_id = ?
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);
    }
}