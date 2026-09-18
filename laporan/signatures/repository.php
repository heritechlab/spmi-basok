<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class LaporanSignatureRepository extends BaseRepository
{
    protected string $table = 'laporan_ami_signatures';

    public function find(string $reportType, int $periodId, ?int $unitId = null): ?array
    {
        if ($unitId !== null) {
            $stmt = $this->prepare("SELECT * FROM laporan_ami_signatures WHERE report_type = ? AND unit_id = ? AND period_id = ? LIMIT 1");
            $stmt->bind_param("sii", $reportType, $unitId, $periodId);
        } else {
            $stmt = $this->prepare("SELECT * FROM laporan_ami_signatures WHERE report_type = ? AND unit_id IS NULL AND period_id = ? LIMIT 1");
            $stmt->bind_param("si", $reportType, $periodId);
        }

        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function upsert(array $data): int
    {
        $existing = $this->find($data['report_type'], $data['period_id'], $data['unit_id']);

        if ($existing) {

            $stmt = $this->prepare("
                UPDATE laporan_ami_signatures
                SET ketua_tim_nama = ?, ketua_tim_jabatan = ?, ketua_tim_ttd = ?, ketua_tim_tanggal = ?,
                    ketua_lpm_nama = ?, ketua_lpm_ttd = ?, ketua_lpm_tanggal = ?,
                    ketua_institusi_nama = ?, ketua_institusi_ttd = ?, ketua_institusi_tanggal = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssssssssssi",
                $data['ketua_tim_nama'], $data['ketua_tim_jabatan'], $data['ketua_tim_ttd'], $data['ketua_tim_tanggal'],
                $data['ketua_lpm_nama'], $data['ketua_lpm_ttd'], $data['ketua_lpm_tanggal'],
                $data['ketua_institusi_nama'], $data['ketua_institusi_ttd'], $data['ketua_institusi_tanggal'],
                $existing['id']
            );

            $this->execute($stmt);

            return (int) $existing['id'];
        }

        $stmt = $this->prepare("
            INSERT INTO laporan_ami_signatures
                (report_type, unit_id, period_id,
                 ketua_tim_nama, ketua_tim_jabatan, ketua_tim_ttd, ketua_tim_tanggal,
                 ketua_lpm_nama, ketua_lpm_ttd, ketua_lpm_tanggal,
                 ketua_institusi_nama, ketua_institusi_ttd, ketua_institusi_tanggal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "siissssssssss",
            $data['report_type'], $data['unit_id'], $data['period_id'],
            $data['ketua_tim_nama'], $data['ketua_tim_jabatan'], $data['ketua_tim_ttd'], $data['ketua_tim_tanggal'],
            $data['ketua_lpm_nama'], $data['ketua_lpm_ttd'], $data['ketua_lpm_tanggal'],
            $data['ketua_institusi_nama'], $data['ketua_institusi_ttd'], $data['ketua_institusi_tanggal']
        );

        $this->execute($stmt);

        return $this->insertId();
    }
}