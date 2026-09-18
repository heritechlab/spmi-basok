<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class GkmRepository extends BaseRepository
{
    protected string $table = 'gkm_monitoring';

    public function getChecklistItems(): array
    {
        $stmt = $this->prepare("
            SELECT id, tahap, item_text, sort_order
            FROM gkm_checklist_items
            WHERE is_active = 1
            ORDER BY FIELD(tahap, 'Perencanaan', 'Proses', 'Pelaporan'), sort_order ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAll(int $unitId = 0, string $semester = '', string $academicYear = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT gm.*, u.code AS unit_code, u.name AS unit_name
            FROM gkm_monitoring gm
            LEFT JOIN units u ON u.id = gm.unit_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if ($unitId > 0) {
            $sql .= " AND gm.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($semester !== '') {
            $sql .= " AND gm.semester = ? ";
            $types .= "s";
            $params[] = $semester;
        }

        if ($academicYear !== '') {
            $sql .= " AND gm.academic_year = ? ";
            $types .= "s";
            $params[] = $academicYear;
        }

        $sql .= " ORDER BY gm.created_at DESC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function count(int $unitId = 0, string $semester = '', string $academicYear = ''): int
    {
        $sql = "SELECT COUNT(*) AS total FROM gkm_monitoring WHERE 1 = 1";

        $types = '';
        $params = [];

        if ($unitId > 0) {
            $sql .= " AND unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($semester !== '') {
            $sql .= " AND semester = ? ";
            $types .= "s";
            $params[] = $semester;
        }

        if ($academicYear !== '') {
            $sql .= " AND academic_year = ? ";
            $types .= "s";
            $params[] = $academicYear;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("
            SELECT gm.*, u.code AS unit_code, u.name AS unit_name
            FROM gkm_monitoring gm
            LEFT JOIN units u ON u.id = gm.unit_id
            WHERE gm.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function existsForUnit(int $unitId, string $semester, string $academicYear): bool
    {
        $stmt = $this->prepare("SELECT id FROM gkm_monitoring WHERE unit_id = ? AND semester = ? AND academic_year = ? LIMIT 1");
        $stmt->bind_param("iss", $unitId, $semester, $academicYear);
        $this->execute($stmt);

        return $this->fetchOne($stmt) !== null;
    }

public function create(int $unitId, string $semester, string $academicYear, string $gkmNama, int $createdBy): int
    {
        $stmt = $this->prepare("
            INSERT INTO gkm_monitoring (unit_id, semester, academic_year, gkm_nama, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssi", $unitId, $semester, $academicYear, $gkmNama, $createdBy);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM gkm_monitoring WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function getItemResponses(int $monitoringId): array
    {
        $stmt = $this->prepare("
            SELECT checklist_item_id, status, catatan
            FROM gkm_monitoring_items
            WHERE monitoring_id = ?
        ");
        $stmt->bind_param("i", $monitoringId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['checklist_item_id']] = $row;
        }

        return $map;
    }

    public function saveItemResponse(int $monitoringId, int $checklistItemId, string $status, string $catatan): void
    {
        $stmt = $this->prepare("
            INSERT INTO gkm_monitoring_items (monitoring_id, checklist_item_id, status, catatan)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), catatan = VALUES(catatan)
        ");
        $stmt->bind_param("iiss", $monitoringId, $checklistItemId, $status, $catatan);
        $this->execute($stmt);
    }

    public function getCompletionStats(int $monitoringId): array
    {
        $stmt = $this->prepare("
            SELECT
                ci.tahap,
                COUNT(ci.id) AS total_item,
                SUM(CASE WHEN gmi.status = 'Sudah' THEN 1 ELSE 0 END) AS done_item
            FROM gkm_checklist_items ci
            LEFT JOIN gkm_monitoring_items gmi ON gmi.checklist_item_id = ci.id AND gmi.monitoring_id = ?
            WHERE ci.is_active = 1
            GROUP BY ci.tahap
        ");
        $stmt->bind_param("i", $monitoringId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function getInstitutionSummary(string $semester, string $academicYear): array
    {
        $stmt = $this->prepare("
            SELECT gm.id, gm.unit_id, u.code AS unit_code, u.name AS unit_name
            FROM gkm_monitoring gm
            LEFT JOIN units u ON u.id = gm.unit_id
            WHERE gm.semester = ? AND gm.academic_year = ?
            ORDER BY u.name ASC
        ");
        $stmt->bind_param("ss", $semester, $academicYear);
        $this->execute($stmt);

        $monitorings = $this->fetchAll($stmt);

        foreach ($monitorings as &$m) {
            $m['stats'] = $this->getCompletionStats((int) $m['id']);
        }
        unset($m);

        return $monitorings;
    }
    public function updateGkmSigner(int $id, string $gkmNama, string $gkmJabatan): void
    {
        $stmt = $this->prepare("UPDATE gkm_monitoring SET gkm_nama = ?, gkm_jabatan = ? WHERE id = ?");
        $stmt->bind_param("ssi", $gkmNama, $gkmJabatan, $id);
        $this->execute($stmt);
    }
    public function updateSignatures(int $id, array $data): void
    {
        $stmt = $this->prepare("
            UPDATE gkm_monitoring
            SET gkm_nama = ?, gkm_jabatan = ?, gkm_ttd = ?, gkm_tanggal = ?,
                lpm_nama = ?, lpm_ttd = ?, lpm_tanggal = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssssi",
            $data['gkm_nama'],
            $data['gkm_jabatan'],
            $data['gkm_ttd'],
            $data['gkm_tanggal'],
            $data['lpm_nama'],
            $data['lpm_ttd'],
            $data['lpm_tanggal'],
            $id
        );
        $this->execute($stmt);
    }
}