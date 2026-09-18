<?php

declare(strict_types=1);

class PeriodeCopyService
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function copy(int $sourcePeriodeId, int $targetPeriodeId): array
    {
        if ($sourcePeriodeId <= 0 || $targetPeriodeId <= 0 || $sourcePeriodeId === $targetPeriodeId) {
            throw new InvalidArgumentException('Periode sumber dan tujuan tidak valid.');
        }

        $this->clearTargetPeriode($targetPeriodeId);

        $reIdMap = $this->copyRencanaEvaluasi($sourcePeriodeId, $targetPeriodeId);
        $rpsCount = $this->copyRps($sourcePeriodeId, $targetPeriodeId, $reIdMap);
        $timCount = $this->copyTimTeaching($sourcePeriodeId, $targetPeriodeId);
        $rubrikCount = $this->copyRubrikPenilaian($sourcePeriodeId, $targetPeriodeId);

        return [
            'rencana_evaluasi' => count($reIdMap),
            'rps'              => $rpsCount,
            'tim_teaching'     => $timCount,
            'rubrik_penilaian' => $rubrikCount,
        ];
    }

        private function clearTargetPeriode(int $periodeId): void
    {
        $rpsIds = [];
        $stmtRps = $this->conn->prepare("SELECT id FROM obe_rps WHERE periode_id = ?");
        $stmtRps->bind_param("i", $periodeId);
        $stmtRps->execute();
        foreach ($stmtRps->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $rpsIds[] = (int) $r['id'];
        }

        $reIds = [];
        $stmtRe = $this->conn->prepare("SELECT id FROM obe_rencana_evaluasi WHERE periode_id = ?");
        $stmtRe->bind_param("i", $periodeId);
        $stmtRe->execute();
        foreach ($stmtRe->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $reIds[] = (int) $r['id'];
        }

        if (!empty($rpsIds)) {
            $placeholders = implode(',', array_fill(0, count($rpsIds), '?'));
            $types = str_repeat('i', count($rpsIds));

            foreach (['obe_rps_bentuk_luring_map', 'obe_rps_metode_luring_map', 'obe_rps_metode_daring_map', 'obe_rps_bahan_kajian_map', 'obe_rps_rencana_evaluasi_map'] as $table) {
                $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE rps_id IN ({$placeholders})");
                $stmt->bind_param($types, ...$rpsIds);
                $stmt->execute();
            }
        }

        if (!empty($reIds)) {
            $placeholders = implode(',', array_fill(0, count($reIds), '?'));
            $types = str_repeat('i', count($reIds));

            foreach (['obe_rencana_evaluasi_map', 'obe_rencana_evaluasi_indikator_map'] as $table) {
                $stmt = $this->conn->prepare("DELETE FROM {$table} WHERE rencana_evaluasi_id IN ({$placeholders})");
                $stmt->bind_param($types, ...$reIds);
                $stmt->execute();
            }
        }

        $stmt1 = $this->conn->prepare("DELETE FROM obe_rps WHERE periode_id = ?");
        $stmt1->bind_param("i", $periodeId);
        $stmt1->execute();

        $stmt2 = $this->conn->prepare("DELETE FROM obe_rencana_evaluasi WHERE periode_id = ?");
        $stmt2->bind_param("i", $periodeId);
        $stmt2->execute();

        $stmt3 = $this->conn->prepare("DELETE FROM obe_mata_kuliah_dosen WHERE periode_id = ?");
        $stmt3->bind_param("i", $periodeId);
        $stmt3->execute();

        $stmt4 = $this->conn->prepare("DELETE FROM obe_rubrik_penilaian_mk WHERE periode_id = ?");
        $stmt4->bind_param("i", $periodeId);
        $stmt4->execute();
    }

    private function copySimpleMap(string $table, string $parentCol, string $childCol, int $oldParentId, int $newParentId): void
    {
        $stmt = $this->conn->prepare("SELECT {$childCol} FROM {$table} WHERE {$parentCol} = ?");
        $stmt->bind_param("i", $oldParentId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($rows)) {
            return;
        }

        $insert = $this->conn->prepare("INSERT INTO {$table} ({$parentCol}, {$childCol}) VALUES (?, ?)");

        foreach ($rows as $row) {
            $childId = (int) $row[$childCol];
            $insert->bind_param("ii", $newParentId, $childId);
            $insert->execute();
        }
    }

    private function copyRencanaEvaluasi(int $src, int $tgt): array
    {
        $idMap = [];

        $stmt = $this->conn->prepare("SELECT * FROM obe_rencana_evaluasi WHERE periode_id = ?");
        $stmt->bind_param("i", $src);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $insert = $this->conn->prepare("
            INSERT INTO obe_rencana_evaluasi
                (mata_kuliah_id, periode_id, pertemuan, basis_evaluasi, komponen_siakad, bobot_persen, deskripsi, deskripsi_eng, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($rows as $row) {
            $mkId = (int) $row['mata_kuliah_id'];
            $pertemuan = $row['pertemuan'] !== null ? (int) $row['pertemuan'] : null;
            $basis = $row['basis_evaluasi'];
            $komponen = $row['komponen_siakad'];
            $bobot = (float) $row['bobot_persen'];
            $deskripsi = $row['deskripsi'];
            $deskripsiEng = $row['deskripsi_eng'];
            $sortOrder = (int) $row['sort_order'];

            $insert->bind_param("iiissdssi", $mkId, $tgt, $pertemuan, $basis, $komponen, $bobot, $deskripsi, $deskripsiEng, $sortOrder);
            $insert->execute();
            $newId = $insert->insert_id;
            $idMap[(int) $row['id']] = $newId;

            $this->copySimpleMap('obe_rencana_evaluasi_map', 'rencana_evaluasi_id', 'sub_cpmk_id', (int) $row['id'], $newId);
            $this->copySimpleMap('obe_rencana_evaluasi_indikator_map', 'rencana_evaluasi_id', 'indikator_penilaian_id', (int) $row['id'], $newId);
        }

        return $idMap;
    }

    private function copyRps(int $src, int $tgt, array $reIdMap): int
    {
        $stmt = $this->conn->prepare("SELECT * FROM obe_rps WHERE periode_id = ?");
        $stmt->bind_param("i", $src);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $insert = $this->conn->prepare("
            INSERT INTO obe_rps
                (mata_kuliah_id, periode_id, pertemuan, sub_cpmk_id, rencana_evaluasi_id, indikator_umum, indikator_khusus, materi_pembelajaran, pustaka, pengalaman_belajar, alokasi_waktu_menit, alokasi_waktu_rincian, bobot_penilaian, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $count = 0;

        foreach ($rows as $row) {
            $mkId = (int) $row['mata_kuliah_id'];
            $pertemuan = (int) $row['pertemuan'];
            $subCpmkId = (int) $row['sub_cpmk_id'];
            $oldReId = $row['rencana_evaluasi_id'] !== null ? (int) $row['rencana_evaluasi_id'] : null;
            $newReId = ($oldReId && isset($reIdMap[$oldReId])) ? $reIdMap[$oldReId] : null;
            $indikatorUmum = $row['indikator_umum'];
            $indikatorKhusus = $row['indikator_khusus'];
            $materi = $row['materi_pembelajaran'];
            $pustaka = $row['pustaka'];
            $pengalaman = $row['pengalaman_belajar'];
            $alokasiMenit = (int) $row['alokasi_waktu_menit'];
            $alokasiRincian = $row['alokasi_waktu_rincian'];
            $bobot = (float) $row['bobot_penilaian'];
            $sortOrder = (int) $row['sort_order'];

            $insert->bind_param(
                "iiiiisssssisdi",
                $mkId, $tgt, $pertemuan, $subCpmkId, $newReId,
                $indikatorUmum, $indikatorKhusus, $materi, $pustaka, $pengalaman,
                $alokasiMenit, $alokasiRincian, $bobot, $sortOrder
            );
            $insert->execute();
            $newRpsId = $insert->insert_id;
            $count++;

            $oldRpsId = (int) $row['id'];

            $this->copySimpleMap('obe_rps_bentuk_luring_map', 'rps_id', 'bentuk_pembelajaran_id', $oldRpsId, $newRpsId);
            $this->copySimpleMap('obe_rps_metode_luring_map', 'rps_id', 'metode_pembelajaran_id', $oldRpsId, $newRpsId);
            $this->copySimpleMap('obe_rps_metode_daring_map', 'rps_id', 'metode_pembelajaran_id', $oldRpsId, $newRpsId);
            $this->copySimpleMap('obe_rps_bahan_kajian_map', 'rps_id', 'bahan_kajian_id', $oldRpsId, $newRpsId);

            $stmtRe = $this->conn->prepare("SELECT rencana_evaluasi_id, bobot_persen FROM obe_rps_rencana_evaluasi_map WHERE rps_id = ?");
            $stmtRe->bind_param("i", $oldRpsId);
            $stmtRe->execute();
            $reMapRows = $stmtRe->get_result()->fetch_all(MYSQLI_ASSOC);

            $insertMap = $this->conn->prepare("INSERT INTO obe_rps_rencana_evaluasi_map (rps_id, rencana_evaluasi_id, bobot_persen) VALUES (?, ?, ?)");

            foreach ($reMapRows as $reMapRow) {
                $oldReMapId = (int) $reMapRow['rencana_evaluasi_id'];
                $newReMapId = $reIdMap[$oldReMapId] ?? null;

                if (!$newReMapId) {
                    continue;
                }

                $bobotPersen = (float) $reMapRow['bobot_persen'];
                $insertMap->bind_param("iid", $newRpsId, $newReMapId, $bobotPersen);
                $insertMap->execute();
            }
        }

        return $count;
    }

    private function copyTimTeaching(int $src, int $tgt): int
    {
        $stmt = $this->conn->prepare("SELECT mata_kuliah_id, dosen_id, peran FROM obe_mata_kuliah_dosen WHERE periode_id = ?");
        $stmt->bind_param("i", $src);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $insert = $this->conn->prepare("INSERT INTO obe_mata_kuliah_dosen (mata_kuliah_id, periode_id, dosen_id, peran) VALUES (?, ?, ?, ?)");
        $count = 0;

        foreach ($rows as $row) {
            $mkId = (int) $row['mata_kuliah_id'];
            $dosenId = (int) $row['dosen_id'];
            $peran = $row['peran'];
            $insert->bind_param("iiis", $mkId, $tgt, $dosenId, $peran);
            $insert->execute();
            $count++;
        }

        return $count;
    }

    private function copyRubrikPenilaian(int $src, int $tgt): int
    {
        $stmt = $this->conn->prepare("SELECT mata_kuliah_id, indikator_penilaian_id, catatan FROM obe_rubrik_penilaian_mk WHERE periode_id = ?");
        $stmt->bind_param("i", $src);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($rows)) {
            return 0;
        }

        $insert = $this->conn->prepare("INSERT INTO obe_rubrik_penilaian_mk (mata_kuliah_id, periode_id, indikator_penilaian_id, catatan) VALUES (?, ?, ?, ?)");
        $count = 0;

        foreach ($rows as $row) {
            $mkId = (int) $row['mata_kuliah_id'];
            $indId = (int) $row['indikator_penilaian_id'];
            $catatan = $row['catatan'];
            $insert->bind_param("iiis", $mkId, $tgt, $indId, $catatan);
            $insert->execute();
            $count++;
        }

        return $count;
    }
}