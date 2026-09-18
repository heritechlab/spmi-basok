<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class RencanaEvaluasiRepository extends BaseRepository
{
    protected string $table = 'obe_rencana_evaluasi';

    public function getAllByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_rencana_evaluasi
            WHERE mata_kuliah_id = ? AND periode_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['sub_cpmk_list'] = $this->getMappedSubCpmk((int) $row['id']);
            $row['siakad_bobot_list'] = $this->getSiakadBobot((int) $row['id']);
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_rencana_evaluasi WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        if ($row) {
            $row['sub_cpmk_ids'] = array_column($this->getMappedSubCpmk($id), 'id');
            $row['siakad_bobot_list'] = $this->getSiakadBobot($id);
        }

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_rencana_evaluasi
                (mata_kuliah_id, periode_id, pertemuan, basis_evaluasi, komponen_siakad, bobot_persen, deskripsi, deskripsi_eng, sort_order,
                 indikator_kognitif, indikator_afektif, indikator_psikomotorik, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iiissdssisss",
            $data['mata_kuliah_id'], $data['periode_id'], $data['pertemuan'], $data['basis_evaluasi'], $data['komponen_siakad'],
            $data['bobot_persen'], $data['deskripsi'], $data['deskripsi_eng'], $data['sort_order'],
            $data['indikator_kognitif'], $data['indikator_afektif'], $data['indikator_psikomotorik']
        );
        $this->execute($stmt);

        $id = $this->insertId();

        $this->saveSubCpmkMapping($id, $data['sub_cpmk_ids'] ?? []);
        $this->saveSiakadBobot($id, $data['siakad_rincian'] ?? []);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_rencana_evaluasi
            SET pertemuan = ?, basis_evaluasi = ?, komponen_siakad = ?, bobot_persen = ?, deskripsi = ?, deskripsi_eng = ?, sort_order = ?,
                indikator_kognitif = ?, indikator_afektif = ?, indikator_psikomotorik = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "issdssisssi",
            $data['pertemuan'], $data['basis_evaluasi'], $data['komponen_siakad'], $data['bobot_persen'],
            $data['deskripsi'], $data['deskripsi_eng'], $data['sort_order'],
            $data['indikator_kognitif'], $data['indikator_afektif'], $data['indikator_psikomotorik'], $id
        );
        $this->execute($stmt);

        $this->saveSubCpmkMapping($id, $data['sub_cpmk_ids'] ?? []);
        $this->saveSiakadBobot($id, $data['siakad_rincian'] ?? []);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_rencana_evaluasi WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function getTotalBobot(int $mataKuliahId, int $periodeId, int $excludeId = 0): float
    {
        $sql = "SELECT SUM(bobot_persen) AS total FROM obe_rencana_evaluasi WHERE mata_kuliah_id = ? AND periode_id = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("iii", $mataKuliahId, $periodeId, $excludeId);
        } else {
            $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (float) ($row['total'] ?? 0);
    }

        public function getMappedSubCpmk(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT sc.id, sc.code, sc.bobot, c.code AS cpmk_code
            FROM obe_rencana_evaluasi_map m
            JOIN obe_sub_cpmk sc ON sc.id = m.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            WHERE m.rencana_evaluasi_id = ?
            ORDER BY c.sort_order ASC, sc.sort_order ASC
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

        public function getSiakadBobot(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT komponen_siakad, bobot_persen
            FROM obe_rencana_evaluasi_siakad_bobot
            WHERE rencana_evaluasi_id = ?
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveSiakadBobot(int $reoId, array $rincian): void
    {
        $delete = $this->prepare("DELETE FROM obe_rencana_evaluasi_siakad_bobot WHERE rencana_evaluasi_id = ?");
        $delete->bind_param("i", $reoId);
        $this->execute($delete);

        if (empty($rincian)) {
            return;
        }

        $insert = $this->prepare("
            INSERT INTO obe_rencana_evaluasi_siakad_bobot (rencana_evaluasi_id, komponen_siakad, bobot_persen)
            VALUES (?, ?, ?)
        ");

        foreach ($rincian as $item) {
            $komponen = trim($item['komponen'] ?? '');
            $bobot = (float) ($item['bobot'] ?? 0);

            if ($komponen === '') {
                continue;
            }

            $insert->bind_param("isd", $reoId, $komponen, $bobot);
            $this->execute($insert);
        }
    }

    public function saveSubCpmkMapping(int $reoId, array $subCpmkIds): void
    {
        $delete = $this->prepare("DELETE FROM obe_rencana_evaluasi_map WHERE rencana_evaluasi_id = ?");
        $delete->bind_param("i", $reoId);
        $this->execute($delete);

        if (empty($subCpmkIds)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_rencana_evaluasi_map (rencana_evaluasi_id, sub_cpmk_id) VALUES (?, ?)");

        foreach ($subCpmkIds as $subCpmkId) {
            $subCpmkId = (int) $subCpmkId;
            $insert->bind_param("ii", $reoId, $subCpmkId);
            $this->execute($insert);
        }
    }

        public function getSubCpmkByCpmkTemp(int $cpmkId): array
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
        public function getMappedIndikator(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT ip.id, ip.indikator, ip.taksonomi_ranah, ip.taksonomi_jenjang
            FROM obe_rencana_evaluasi_indikator_map m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            WHERE m.rencana_evaluasi_id = ?
            ORDER BY ip.sort_order ASC
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveIndikatorMapping(int $reoId, array $indikatorIds): void
    {
        $delete = $this->prepare("DELETE FROM obe_rencana_evaluasi_indikator_map WHERE rencana_evaluasi_id = ?");
        $delete->bind_param("i", $reoId);
        $this->execute($delete);

        if (empty($indikatorIds)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_rencana_evaluasi_indikator_map (rencana_evaluasi_id, indikator_penilaian_id) VALUES (?, ?)");

        foreach ($indikatorIds as $indikatorId) {
            $indikatorId = (int) $indikatorId;
            $insert->bind_param("ii", $reoId, $indikatorId);
            $this->execute($insert);
        }
    }

    public function getIndikatorByBentuk(string $bentukPenilaian): array
    {
        $stmt = $this->prepare("
            SELECT id, indikator, taksonomi_ranah, taksonomi_jenjang
            FROM obe_indikator_penilaian
            WHERE teknik_penilaian = ?
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("s", $bentukPenilaian);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getTotalBobotByBasisEvaluasi(int $mataKuliahId, int $periodeId, string $basisEvaluasi, int $excludeId = 0): float
    {
        $sql = "SELECT SUM(bobot_persen) AS total FROM obe_rencana_evaluasi WHERE mata_kuliah_id = ? AND periode_id = ? AND basis_evaluasi = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("iisi", $mataKuliahId, $periodeId, $basisEvaluasi, $excludeId);
        } else {
            $stmt->bind_param("iis", $mataKuliahId, $periodeId, $basisEvaluasi);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (float) ($row['total'] ?? 0);
    }
}