<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class RubrikPenilaianRepository extends BaseRepository
{
    protected string $table = 'obe_rubrik_penilaian_mk';

    public function getAllIndikatorWithKriteria(): array
    {
        $stmt = $this->prepare("
            SELECT ip.id, ip.teknik_penilaian, ip.taksonomi_ranah, ip.taksonomi_jenjang, ip.indikator, ip.sort_order,
                   GROUP_CONCAT(rk.nama_kriteria ORDER BY rk.sort_order SEPARATOR '|||') AS kriteria_list
            FROM obe_indikator_penilaian ip
            LEFT JOIN obe_rubrik_kriteria rk ON rk.indikator_penilaian_id = ip.id
            GROUP BY ip.id
            ORDER BY ip.teknik_penilaian ASC, ip.sort_order ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMappedByMataKuliah(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT indikator_penilaian_id, catatan
            FROM obe_rubrik_penilaian_mk
            WHERE mata_kuliah_id = ? AND periode_id = ?
        ");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function replaceForMataKuliah(int $mkId, int $periodeId, array $rows): void
    {
        $delete = $this->prepare("DELETE FROM obe_rubrik_penilaian_mk WHERE mata_kuliah_id = ? AND periode_id = ?");
        $delete->bind_param("ii", $mkId, $periodeId);
        $this->execute($delete);

        if (empty($rows)) {
            return;
        }

        $insert = $this->prepare("
            INSERT INTO obe_rubrik_penilaian_mk (mata_kuliah_id, periode_id, indikator_penilaian_id, catatan)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            $indId = (int) ($row['indikator_penilaian_id'] ?? 0);
            $catatan = trim($row['catatan'] ?? '') ?: null;

            if ($indId <= 0) {
                continue;
            }

            $insert->bind_param("iiis", $mkId, $periodeId, $indId, $catatan);
            $this->execute($insert);
        }
    }
}