<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class RencanaTugasRepository extends BaseRepository
{
    protected string $table = 'obe_rencana_tugas';

    private const JENIS_TUGAS = ['Aktivitas Partisipatif', 'Hasil Proyek', 'Tugas'];

    public function getQualifyingRencanaEvaluasi(int $mkId, int $periodeId): array
    {
        $placeholders = implode(',', array_fill(0, count(self::JENIS_TUGAS), '?'));
        $types = 'ii' . str_repeat('s', count(self::JENIS_TUGAS));

        $stmt = $this->prepare("
            SELECT id, pertemuan, basis_evaluasi, bobot_persen
            FROM obe_rencana_evaluasi
            WHERE mata_kuliah_id = ? AND periode_id = ? AND basis_evaluasi IN ({$placeholders})
            ORDER BY pertemuan ASC, id ASC
        ");
        $params = array_merge([$mkId, $periodeId], self::JENIS_TUGAS);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['sub_cpmk_list'] = $this->getSubCpmkForRencanaEvaluasi((int) $row['id']);
            $row['indikator_list'] = $this->getIndikatorForRencanaEvaluasi((int) $row['id']);
        }

        return $rows;
    }

    public function getSubCpmkForRencanaEvaluasi(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT sc.code, c.code AS cpmk_code
            FROM obe_rencana_evaluasi_map m
            JOIN obe_sub_cpmk sc ON sc.id = m.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            WHERE m.rencana_evaluasi_id = ?
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getIndikatorForRencanaEvaluasi(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT ip.indikator
            FROM obe_rencana_evaluasi_indikator_map m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            WHERE m.rencana_evaluasi_id = ?
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getByMataKuliah(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT rt.*
            FROM obe_rencana_tugas rt
            WHERE rt.mata_kuliah_id = ? AND rt.periode_id = ?
        ");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function upsert(array $data): void
    {
        $stmt = $this->prepare("
            INSERT INTO obe_rencana_tugas
                (mata_kuliah_id, periode_id, rencana_evaluasi_id, bentuk_tugas, judul, deskripsi_tugas, metode_pengerjaan, bentuk_luaran, kriteria_penilaian, minggu_mulai, minggu_selesai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                bentuk_tugas = VALUES(bentuk_tugas),
                judul = VALUES(judul),
                deskripsi_tugas = VALUES(deskripsi_tugas),
                metode_pengerjaan = VALUES(metode_pengerjaan),
                bentuk_luaran = VALUES(bentuk_luaran),
                kriteria_penilaian = VALUES(kriteria_penilaian),
                minggu_mulai = VALUES(minggu_mulai),
                minggu_selesai = VALUES(minggu_selesai)
        ");
        $stmt->bind_param(
            "iiissssssii",
            $data['mata_kuliah_id'], $data['periode_id'], $data['rencana_evaluasi_id'],
            $data['bentuk_tugas'], $data['judul'], $data['deskripsi_tugas'],
            $data['metode_pengerjaan'], $data['bentuk_luaran'], $data['kriteria_penilaian'],
            $data['minggu_mulai'], $data['minggu_selesai']
        );
        $this->execute($stmt);
    }
}