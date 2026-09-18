<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class LedRepository extends BaseRepository
{
    protected string $table = 'desk_evaluation_items';
    private mysqli $dbConn;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        $this->dbConn = $conn;
    }

    public function getKriteria(string $level): array
    {
        if ($level === 'prodi') {
            $stmt = $this->prepare("SELECT id, name FROM acc_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        } else {
            $stmt = $this->prepare("SELECT id, parent_id, name FROM institution_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        }

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMyUnitId(int $userId): int
    {
        $stmt = $this->prepare("SELECT unit_id FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int) ($row['unit_id'] ?? 0);
    }

    public function getMyAssignments(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT a.id, a.assignment_number, a.audit_date, a.status, p.period_name
            FROM audit_assignments a
            LEFT JOIN audit_periods p ON p.id = a.period_id
            WHERE a.auditee_id = ?
            ORDER BY a.audit_date DESC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAssignmentAuditeeType(int $assignmentId): ?array
    {
        $stmt = $this->prepare("
            SELECT a.auditee_id, u.type
            FROM audit_assignments a
            JOIN units u ON u.id = a.auditee_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getAssignment(int $assignmentId): ?array
    {
        $stmt = $this->prepare("
            SELECT a.id, a.assignment_number, a.auditee_id, u.name AS auditee_name
            FROM audit_assignments a
            LEFT JOIN units u ON u.id = a.auditee_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getAssignedStandardIds(int $assignmentId): array
    {
        $stmt = $this->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");
        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);
        $ids = array_map('intval', array_column($this->fetchAll($stmt), 'standard_id'));

        if (!empty($ids)) {
            return $ids;
        }

        $stmtAll = $this->prepare("SELECT id FROM standards WHERE is_active = 1");
        $this->execute($stmtAll);

        return array_map('intval', array_column($this->fetchAll($stmtAll), 'id'));
    }

    public function getStandardsByKriteria(string $level, int $criteriaId, array $allowedStandardIds): array
    {
        $column = $level === 'prodi' ? 'prodi_criteria_id' : 'institution_criteria_id';

        if (empty($allowedStandardIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($allowedStandardIds), '?'));
        $types = 'i' . str_repeat('i', count($allowedStandardIds));
        $params = array_merge([$criteriaId], $allowedStandardIds);

        $stmt = $this->prepare("
            SELECT id, code, name
            FROM standards
            WHERE {$column} = ? AND is_active = 1 AND id IN ({$placeholders})
            ORDER BY code ASC
        ");
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getIndicatorsByStandard(int $standardId, bool $isProdi = true, int $auditeeUnitId = 0): array
    {
        $ownerFilter = $isProdi
            ? "AND (ss.owner_type IS NULL OR ss.owner_type = 'prodi')"
            : "AND ss.owner_type = 'unit' AND ss.owner_unit_id = " . $auditeeUnitId;

        $stmt = $this->prepare("
            SELECT ai.id, ai.item_code, ai.statement, ai.indicator, ai.target
            FROM audit_indicators ai
            LEFT JOIN standard_statements ss ON ss.id = ai.statement_id
            WHERE ai.standard_id = ? AND ai.status = 1
              {$ownerFilter}
            ORDER BY ai.sort_order ASC, ai.item_code ASC
        ");
        $stmt->bind_param("i", $standardId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getEntriesByAssignment(array $indicatorIds, int $assignmentId): array
    {
        if (empty($indicatorIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($indicatorIds), '?'));
        $types = str_repeat('i', count($indicatorIds)) . 'i';
        $params = $indicatorIds;
        $params[] = $assignmentId;

        $stmt = $this->prepare("
            SELECT id, audit_indicator_id, notes, capaian_realisasi
            FROM desk_evaluation_items
            WHERE audit_indicator_id IN ({$placeholders}) AND assignment_id = ?
        ");
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);
        $result = [];

        foreach ($rows as $row) {

            $stmtDoc = $this->prepare("
                SELECT id, document_file, document_original_name, link_url
                FROM desk_evaluation_documents
                WHERE desk_evaluation_item_id = ?
            ");
            $stmtDoc->bind_param("i", $row['id']);
            $this->execute($stmtDoc);

            $row['documents'] = $this->fetchAll($stmtDoc);

            $result[(int) $row['audit_indicator_id']] = $row;
        }

        return $result;
    }

    public function getOrCreateEvaluation(int $assignmentId, int $standardId): int
    {
        $stmt = $this->prepare("SELECT id FROM desk_evaluations WHERE assignment_id = ? AND standard_id = ? LIMIT 1");
        $stmt->bind_param("ii", $assignmentId, $standardId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        if ($row) {
            return (int) $row['id'];
        }

        $insert = $this->prepare("INSERT INTO desk_evaluations (assignment_id, standard_id, status, created_at) VALUES (?, ?, 'Belum', NOW())");
        $insert->bind_param("ii", $assignmentId, $standardId);
        $this->execute($insert);

        return $this->insertId();
    }

    public function saveItem(int $assignmentId, int $standardId, int $indicatorId, string $notes, string $capaian): int
    {
        $stmt = $this->prepare("SELECT id FROM desk_evaluation_items WHERE assignment_id = ? AND audit_indicator_id = ? LIMIT 1");
        $stmt->bind_param("ii", $assignmentId, $indicatorId);
        $this->execute($stmt);
        $existing = $this->fetchOne($stmt);

        if ($existing) {
            $itemId = (int) $existing['id'];
            $upd = $this->prepare("UPDATE desk_evaluation_items SET notes = ?, capaian_realisasi = ?, updated_at = NOW() WHERE id = ?");
            $upd->bind_param("ssi", $notes, $capaian, $itemId);
            $this->execute($upd);

            return $itemId;
        }

        $ins = $this->prepare("
            INSERT INTO desk_evaluation_items (assignment_id, standard_id, audit_indicator_id, notes, capaian_realisasi, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $ins->bind_param("iiiss", $assignmentId, $standardId, $indicatorId, $notes, $capaian);
        $this->execute($ins);

        return $this->insertId();
    }

    public function saveLink(int $itemId, string $linkUrl): void
    {
        $insert = $this->prepare("INSERT INTO desk_evaluation_documents (desk_evaluation_item_id, link_url, uploaded_at) VALUES (?, ?, NOW())");
        $insert->bind_param("is", $itemId, $linkUrl);
        $this->execute($insert);
    }

    public function saveDocument(int $itemId, string $file, string $originalName, int $size): void
    {
        $insert = $this->prepare("
            INSERT INTO desk_evaluation_documents (desk_evaluation_item_id, document_file, document_original_name, document_size, uploaded_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $insert->bind_param("issi", $itemId, $file, $originalName, $size);
        $this->execute($insert);
    }

    public function updateEvaluationStatus(int $evalId, string $status): void
    {
        $stmt = $this->prepare("UPDATE desk_evaluations SET status = ?, submitted_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $evalId);
        $this->execute($stmt);
    }

    public function deleteDocument(int $docId, int $myUnitId): bool
    {
        $stmt = $this->prepare("
            SELECT d.id FROM desk_evaluation_documents d
            JOIN desk_evaluation_items i ON i.id = d.desk_evaluation_item_id
            JOIN audit_assignments a ON a.id = i.assignment_id
            WHERE d.id = ? AND a.auditee_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $docId, $myUnitId);
        $this->execute($stmt);

        if (!$this->fetchOne($stmt)) {
            return false;
        }

        $delete = $this->prepare("DELETE FROM desk_evaluation_documents WHERE id = ?");
        $delete->bind_param("i", $docId);
        $this->execute($delete);

        return true;
    }
}