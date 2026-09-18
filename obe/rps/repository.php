<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class RpsRepository extends BaseRepository
{
    protected string $table = 'obe_rps';

    public function getAllByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.*, sc.code AS sub_cpmk_code, sc.description AS sub_cpmk_description, c.code AS cpmk_code
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            WHERE r.mata_kuliah_id = ? AND r.periode_id = ?
            ORDER BY r.pertemuan ASC, r.sort_order ASC
        ");
        $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['bentuk_luring']       = $this->getBentukLuring((int) $row['id']);
            $row['metode_luring']       = $this->getMetodeLuring((int) $row['id']);
            $row['metode_daring']       = $this->getMetodeDaring((int) $row['id']);
            $row['bahan_kajian']        = $this->getBahanKajian((int) $row['id']);
            $row['rencana_evaluasi_list'] = $this->getMappedRencanaEvaluasi((int) $row['id']);
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_rps WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        if ($row) {
            $row['bentuk_luring_ids']    = array_column($this->getBentukLuring($id), 'id');
            $row['metode_luring_ids']    = array_column($this->getMetodeLuring($id), 'id');
            $row['metode_daring_ids']    = array_column($this->getMetodeDaring($id), 'id');
            $row['bahan_kajian_ids']     = array_column($this->getBahanKajian($id), 'id');
            $row['indikator_ids']        = array_column($this->getIndikator($id), 'id');
            $row['rencana_evaluasi_ids'] = array_column($this->getMappedRencanaEvaluasi($id), 'id');
        }

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_rps
                (mata_kuliah_id, periode_id, pertemuan, sub_cpmk_id, rencana_evaluasi_id, indikator_umum, indikator_khusus, materi_pembelajaran, pustaka, pengalaman_belajar, alokasi_waktu_menit, alokasi_waktu_rincian, bobot_penilaian, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iiiiisssssisdi",
            $data['mata_kuliah_id'], $data['periode_id'], $data['pertemuan'], $data['sub_cpmk_id'], $data['rencana_evaluasi_id'],
            $data['indikator_umum'], $data['indikator_khusus'],
            $data['materi_pembelajaran'], $data['pustaka'], $data['pengalaman_belajar'],
            $data['alokasi_waktu_menit'], $data['alokasi_waktu_rincian'], $data['bobot_penilaian'], $data['sort_order']
        );
        $this->execute($stmt);

        $id = $this->insertId();

        $this->saveMappings($id, $data);

        if ($data['rencana_evaluasi_id']) {
            $this->resyncBobotForRencanaEvaluasi((int) $data['rencana_evaluasi_id']);
        }

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $old = $this->findById($id);
        $oldRencanaEvaluasiId = $old['rencana_evaluasi_id'] ?? null;

        $stmt = $this->prepare("
            UPDATE obe_rps
            SET pertemuan = ?, sub_cpmk_id = ?, rencana_evaluasi_id = ?, indikator_umum = ?, indikator_khusus = ?, materi_pembelajaran = ?, pustaka = ?, pengalaman_belajar = ?, alokasi_waktu_menit = ?, alokasi_waktu_rincian = ?, bobot_penilaian = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "iiisssssisdii",
            $data['pertemuan'], $data['sub_cpmk_id'], $data['rencana_evaluasi_id'], $data['indikator_umum'], $data['indikator_khusus'],
            $data['materi_pembelajaran'], $data['pustaka'],
            $data['pengalaman_belajar'], $data['alokasi_waktu_menit'], $data['alokasi_waktu_rincian'], $data['bobot_penilaian'], $data['sort_order'], $id
        );
        $this->execute($stmt);

        $this->saveMappings($id, $data);

        if ($data['rencana_evaluasi_id']) {
            $this->resyncBobotForRencanaEvaluasi((int) $data['rencana_evaluasi_id']);
        }

        if ($oldRencanaEvaluasiId && $oldRencanaEvaluasiId != $data['rencana_evaluasi_id']) {
            $this->resyncBobotForRencanaEvaluasi((int) $oldRencanaEvaluasiId);
        }

        return true;
    }

    public function delete(int $id): bool
    {
        $old = $this->findById($id);
        $rencanaEvaluasiId = $old['rencana_evaluasi_id'] ?? null;

        $stmt = $this->prepare("DELETE FROM obe_rps WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        if ($rencanaEvaluasiId) {
            $this->resyncBobotForRencanaEvaluasi((int) $rencanaEvaluasiId);
        }

        return true;
    }

    public function getTotalBobot(int $mataKuliahId, int $periodeId, int $excludeId = 0): float
    {
        $sql = "SELECT SUM(bobot_penilaian) AS total FROM obe_rps WHERE mata_kuliah_id = ? AND periode_id = ?";

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

    private function saveMappings(int $rpsId, array $data): void
    {
        $this->saveMapMany($rpsId, 'obe_rps_bentuk_luring_map', 'bentuk_pembelajaran_id', $data['bentuk_luring_ids'] ?? []);
        $this->saveMapMany($rpsId, 'obe_rps_metode_luring_map', 'metode_pembelajaran_id', $data['metode_luring_ids'] ?? []);
        $this->saveMapMany($rpsId, 'obe_rps_metode_daring_map', 'metode_pembelajaran_id', $data['metode_daring_ids'] ?? []);
        $this->saveMapMany($rpsId, 'obe_rps_bahan_kajian_map', 'bahan_kajian_id', $data['bahan_kajian_ids'] ?? []);
        $this->saveMapMany($rpsId, 'obe_rps_indikator_map', 'indikator_penilaian_id', $data['indikator_ids'] ?? []);
    }

    private function saveMapMany(int $rpsId, string $table, string $column, array $ids): void
    {
        $delete = $this->prepare("DELETE FROM {$table} WHERE rps_id = ?");
        $delete->bind_param("i", $rpsId);
        $this->execute($delete);

        if (empty($ids)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO {$table} (rps_id, {$column}) VALUES (?, ?)");

        foreach ($ids as $itemId) {
            $itemId = (int) $itemId;
            $insert->bind_param("ii", $rpsId, $itemId);
            $this->execute($insert);
        }
    }

    public function getBentukLuring(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT bp.id, bp.nama_bentuk, bp.kategori
            FROM obe_rps_bentuk_luring_map m
            JOIN obe_master_bentuk_pembelajaran bp ON bp.id = m.bentuk_pembelajaran_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMetodeLuring(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT mp.id, mp.nama_metode, mp.aktivitas_mahasiswa
            FROM obe_rps_metode_luring_map m
            JOIN obe_master_metode_pembelajaran mp ON mp.id = m.metode_pembelajaran_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMetodeDaring(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT mp.id, mp.nama_metode, mp.aktivitas_mahasiswa
            FROM obe_rps_metode_daring_map m
            JOIN obe_master_metode_pembelajaran mp ON mp.id = m.metode_pembelajaran_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getBahanKajian(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT bk.id, bk.nama_bahan_kajian
            FROM obe_rps_bahan_kajian_map m
            JOIN obe_bahan_kajian bk ON bk.id = m.bahan_kajian_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getIndikator(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT ip.id, ip.indikator, ip.taksonomi_ranah, ip.taksonomi_jenjang
            FROM obe_rps_indikator_map m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getRencanaEvaluasiListByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT id, basis_evaluasi, komponen_siakad, bobot_persen
            FROM obe_rencana_evaluasi
            WHERE mata_kuliah_id = ? AND periode_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("ii", $mataKuliahId, $periodeId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {

            $stmtInd = $this->prepare("
                SELECT ip.id, ip.indikator
                FROM obe_rencana_evaluasi_indikator_map m
                JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
                WHERE m.rencana_evaluasi_id = ?
            ");
            $stmtInd->bind_param("i", $row['id']);
            $this->execute($stmtInd);
            $row['indikator'] = $this->fetchAll($stmtInd);
        }

        return $rows;
    }

    public function countRpsByRencanaEvaluasi(int $rencanaEvaluasiId, int $excludeRpsId = 0): int
    {
        $sql = "SELECT COUNT(*) AS total FROM obe_rps WHERE rencana_evaluasi_id = ?";

        if ($excludeRpsId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeRpsId > 0) {
            $stmt->bind_param("ii", $rencanaEvaluasiId, $excludeRpsId);
        } else {
            $stmt->bind_param("i", $rencanaEvaluasiId);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int) ($row['total'] ?? 0);
    }

    public function getRencanaEvaluasiBobot(int $rencanaEvaluasiId): float
    {
        $stmt = $this->prepare("SELECT bobot_persen FROM obe_rencana_evaluasi WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $rencanaEvaluasiId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (float) ($row['bobot_persen'] ?? 0);
    }

    public function resyncBobotForRencanaEvaluasi(int $rencanaEvaluasiId): void
    {
        $totalBobot = $this->getRencanaEvaluasiBobot($rencanaEvaluasiId);
        $jumlahRps = $this->countRpsByRencanaEvaluasi($rencanaEvaluasiId);

        if ($jumlahRps === 0) {
            return;
        }

        $bobotPerBaris = round($totalBobot / $jumlahRps, 2);

        $stmt = $this->prepare("UPDATE obe_rps SET bobot_penilaian = ? WHERE rencana_evaluasi_id = ?");
        $stmt->bind_param("di", $bobotPerBaris, $rencanaEvaluasiId);
        $this->execute($stmt);
    }

        public function getRencanaEvaluasiDetail(int $rencanaEvaluasiId): ?array
    {
        $stmt = $this->prepare("SELECT id, basis_evaluasi FROM obe_rencana_evaluasi WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $rencanaEvaluasiId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        if (!$row) {
            return null;
        }

        $stmtInd = $this->prepare("
            SELECT ip.id, ip.indikator
            FROM obe_rencana_evaluasi_indikator_map m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            WHERE m.rencana_evaluasi_id = ?
        ");
        $stmtInd->bind_param("i", $rencanaEvaluasiId);
        $this->execute($stmtInd);
        $indikatorList = $this->fetchAll($stmtInd);

        foreach ($indikatorList as &$ind) {
            $stmtRub = $this->prepare("SELECT nama_kriteria FROM obe_rubrik_kriteria WHERE indikator_penilaian_id = ? ORDER BY sort_order ASC");
            $stmtRub->bind_param("i", $ind['id']);
            $this->execute($stmtRub);
            $ind['rubrik'] = $this->fetchAll($stmtRub);
        }

        $row['indikator'] = $indikatorList;

        return $row;
    }
    public function getRencanaEvaluasiListByPertemuan(int $mataKuliahId, int $periodeId, int $pertemuan): array
    {
        $stmt = $this->prepare("
            SELECT id, basis_evaluasi, komponen_siakad, bobot_persen,
                   indikator_kognitif, indikator_afektif, indikator_psikomotorik
            FROM obe_rencana_evaluasi
            WHERE mata_kuliah_id = ? AND periode_id = ? AND pertemuan = ?
            ORDER BY id ASC
        ");
        $stmt->bind_param("iii", $mataKuliahId, $periodeId, $pertemuan);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMappedRencanaEvaluasi(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT re.id, re.basis_evaluasi, re.komponen_siakad, re.bobot_persen,
                   re.indikator_kognitif, re.indikator_afektif, re.indikator_psikomotorik
            FROM obe_rps_rencana_evaluasi_map m
            JOIN obe_rencana_evaluasi re ON re.id = m.rencana_evaluasi_id
            WHERE m.rps_id = ?
            ORDER BY re.id ASC
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveRencanaEvaluasiMapping(int $rpsId, array $rencanaEvaluasiIds): void
    {
        $delete = $this->prepare("DELETE FROM obe_rps_rencana_evaluasi_map WHERE rps_id = ?");
        $delete->bind_param("i", $rpsId);
        $this->execute($delete);

        if (empty($rencanaEvaluasiIds)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_rps_rencana_evaluasi_map (rps_id, rencana_evaluasi_id) VALUES (?, ?)");

        foreach ($rencanaEvaluasiIds as $reoId) {
            $reoId = (int) $reoId;
            $insert->bind_param("ii", $rpsId, $reoId);
            $this->execute($insert);
        }
    }

    public function getMappedIndikatorForRencanaEvaluasi(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT ip.indikator, r.nama_kriteria, r.deskripsi AS kriteria_deskripsi
            FROM obe_rencana_evaluasi_indikator_map m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            LEFT JOIN obe_rubrik_kriteria r ON r.indikator_penilaian_id = ip.id
            WHERE m.rencana_evaluasi_id = ?
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function countRpsUsingRencanaEvaluasi(int $rencanaEvaluasiId): int
    {
        $stmt = $this->prepare("SELECT COUNT(*) AS c FROM obe_rps_rencana_evaluasi_map WHERE rencana_evaluasi_id = ?");
        $stmt->bind_param("i", $rencanaEvaluasiId);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        return (int) ($row['c'] ?? 0);
    }

    public function getRpsIdsLinkedToRencanaEvaluasi(array $rencanaEvaluasiIds): array
    {
        if (empty($rencanaEvaluasiIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($rencanaEvaluasiIds), '?'));
        $types = str_repeat('i', count($rencanaEvaluasiIds));

        $stmt = $this->prepare("SELECT DISTINCT rps_id FROM obe_rps_rencana_evaluasi_map WHERE rencana_evaluasi_id IN ($placeholders)");
        $stmt->bind_param($types, ...$rencanaEvaluasiIds);
        $this->execute($stmt);

        return array_column($this->fetchAll($stmt), 'rps_id');
    }

    public function updateBobotPenilaian(int $rpsId, float $bobot): void
    {
        $stmt = $this->prepare("UPDATE obe_rps SET bobot_penilaian = ? WHERE id = ?");
        $stmt->bind_param("di", $bobot, $rpsId);
        $this->execute($stmt);
    }
}