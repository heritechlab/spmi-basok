<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class IkuRepository extends BaseRepository
{
    protected string $table = 'iku_indicators';

    public function getCriteria(): array
    {
        $stmt = $this->prepare("SELECT id, name FROM iku_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getIndicatorsByKategori(string $kategori, int $unitId = 0): array
    {
        $stmt = $this->prepare("
            SELECT i.id, i.criteria_id, i.parent_id, i.code, i.name, i.satuan, i.kategori, i.direction, i.sort_order,
                   c.name AS criteria_name
            FROM iku_indicators i
            JOIN iku_criteria c ON c.id = i.criteria_id
            WHERE i.kategori = ? AND i.is_active = 1 AND i.is_selected = 1
              AND (
                  NOT EXISTS (SELECT 1 FROM iku_indicator_units iu WHERE iu.indicator_id = i.id)
                  OR EXISTS (SELECT 1 FROM iku_indicator_units iu WHERE iu.indicator_id = i.id AND iu.unit_id = ?)
              )
            ORDER BY c.sort_order ASC, i.sort_order ASC
        ");
        $stmt->bind_param("si", $kategori, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getIndicatorUnitIds(int $indicatorId): array
    {
        $stmt = $this->prepare("SELECT unit_id FROM iku_indicator_units WHERE indicator_id = ?");
        $stmt->bind_param("i", $indicatorId);
        $this->execute($stmt);

        return array_column($this->fetchAll($stmt), 'unit_id');
    }

    public function saveIndicatorUnits(int $indicatorId, array $unitIds): void
    {
        $del = $this->prepare("DELETE FROM iku_indicator_units WHERE indicator_id = ?");
        $del->bind_param("i", $indicatorId);
        $this->execute($del);

        if (empty($unitIds)) {
            return;
        }

        $ins = $this->prepare("INSERT INTO iku_indicator_units (indicator_id, unit_id) VALUES (?, ?)");

        foreach ($unitIds as $unitId) {
            $unitId = (int) $unitId;
            $ins->bind_param("ii", $indicatorId, $unitId);
            $this->execute($ins);
        }
    }

    public function getAssignedIndicatorCount(int $unitId, int $tahun, string $triwulan): array
    {
        $stmt = $this->prepare("
            SELECT i.id
            FROM iku_indicators i
            WHERE i.is_active = 1 AND i.is_selected = 1
              AND EXISTS (SELECT 1 FROM iku_indicator_units iu WHERE iu.indicator_id = i.id AND iu.unit_id = ?)
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);
        $assignedIds = array_column($this->fetchAll($stmt), 'id');

        $total = count($assignedIds);
        $filled = 0;

        foreach ($assignedIds as $indId) {

            $stmt2 = $this->prepare("
                SELECT COUNT(*) AS total FROM iku_realizations
                WHERE indicator_id = ? AND unit_id = ? AND tahun = ? AND triwulan = ?
                  AND realisasi IS NOT NULL AND realisasi != ''
            ");
            $stmt2->bind_param("iiis", $indId, $unitId, $tahun, $triwulan);
            $this->execute($stmt2);

            if ((int) ($this->fetchOne($stmt2)['total'] ?? 0) > 0) {
                $filled++;
            }
        }

        return ['total' => $total, 'filled' => $filled, 'pending' => $total - $filled];
    }

    public function getTargets(int $tahun): array
    {
        $stmt = $this->prepare("SELECT indicator_id, baseline, target FROM iku_targets WHERE tahun = ?");
        $stmt->bind_param("i", $tahun);
        $this->execute($stmt);

        $result = [];
        foreach ($this->fetchAll($stmt) as $row) {
            $result[$row['indicator_id']] = $row;
        }

        return $result;
    }

    public function getRealizations(int $tahun, string $triwulan, ?int $unitId): array
    {
        $sql = "SELECT * FROM iku_realizations WHERE tahun = ? AND triwulan = ?";
        $types = "is";
        $params = [$tahun, $triwulan];

        if ($unitId !== null) {
            $sql .= " AND unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        } else {
            $sql .= " AND unit_id IS NULL";
        }

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        $result = [];
        foreach ($this->fetchAll($stmt) as $row) {
            $result[$row['indicator_id']] = $row;
        }

        return $result;
    }

public function saveRealization(array $data): int
    {
        $stmt = $this->prepare("
            SELECT id FROM iku_realizations
            WHERE indicator_id = ? AND tahun = ? AND triwulan = ?
              AND (unit_id = ? OR (unit_id IS NULL AND ? IS NULL))
            LIMIT 1
        ");
        $unitIdCheck = $data['unit_id'];
        $stmt->bind_param(
            "iisii",
            $data['indicator_id'], $data['tahun'], $data['triwulan'], $unitIdCheck, $unitIdCheck
        );
        $this->execute($stmt);
        $existing = $this->fetchOne($stmt);

        if ($existing) {

            $stmt2 = $this->prepare("
                UPDATE iku_realizations
                SET realisasi = ?, analisis = ?, updated_by = ?
                WHERE id = ?
            ");
            $stmt2->bind_param(
                "ssii",
                $data['realisasi'], $data['analisis'], $data['updated_by'], $existing['id']
            );
            $this->execute($stmt2);

            if (!empty($data['bukti_file'])) {
                $stmt3 = $this->prepare("UPDATE iku_realizations SET bukti_file = ?, bukti_original_name = ? WHERE id = ?");
                $stmt3->bind_param("ssi", $data['bukti_file'], $data['bukti_original_name'], $existing['id']);
                $this->execute($stmt3);
            }

            return (int) $existing['id'];
        }

        $stmt4 = $this->prepare("
            INSERT INTO iku_realizations (indicator_id, unit_id, tahun, triwulan, realisasi, analisis, bukti_file, bukti_original_name, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt4->bind_param(
            "iiisssssi",
            $data['indicator_id'], $data['unit_id'], $data['tahun'], $data['triwulan'],
            $data['realisasi'], $data['analisis'], $data['bukti_file'], $data['bukti_original_name'], $data['updated_by']
        );
        $this->execute($stmt4);

        return $this->insertId();
    }

    public function getProdiUnits(): array
    {
        $stmt = $this->prepare("SELECT id, code, name FROM units ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function getAllIndicatorsFlat(): array
    {
        $stmt = $this->prepare("
            SELECT i.*, c.name AS criteria_name, p.code AS parent_code
            FROM iku_indicators i
            JOIN iku_criteria c ON c.id = i.criteria_id
            LEFT JOIN iku_indicators p ON p.id = i.parent_id
            WHERE i.is_active = 1
            ORDER BY i.kategori ASC, c.sort_order ASC, i.parent_id IS NULL DESC, i.sort_order ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function updateIndicator(int $id, array $data): void
    {
        $stmt = $this->prepare("
            UPDATE iku_indicators
            SET code = ?, name = ?, satuan = ?, direction = ?, kategori = ?, is_selected = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssii",
            $data['code'], $data['name'], $data['satuan'], $data['direction'], $data['kategori'], $data['is_selected'], $id
        );
        $this->execute($stmt);
    }

    public function upsertTarget(int $indicatorId, int $tahun, string $baseline, string $target): void
    {
        $stmt = $this->prepare("SELECT id FROM iku_targets WHERE indicator_id = ? AND tahun = ? LIMIT 1");
        $stmt->bind_param("ii", $indicatorId, $tahun);
        $this->execute($stmt);
        $existing = $this->fetchOne($stmt);

        if ($existing) {
            $stmt2 = $this->prepare("UPDATE iku_targets SET baseline = ?, target = ? WHERE id = ?");
            $stmt2->bind_param("ssi", $baseline, $target, $existing['id']);
            $this->execute($stmt2);
            return;
        }

        $stmt3 = $this->prepare("INSERT INTO iku_targets (indicator_id, tahun, baseline, target) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("iiss", $indicatorId, $tahun, $baseline, $target);
        $this->execute($stmt3);
    }
}