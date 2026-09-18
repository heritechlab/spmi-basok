<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class FindingRepository extends BaseRepository
{
    protected string $table = 'audit_checklist_results';

private function baseSelect(): string
    {
        return "
            SELECT
                r.id, r.audit_status, r.achievement, r.finding, r.root_cause,
                r.supporting_factor, r.recommendation, r.audited_at,
                r.document_audit_result, r.field_audit_result, r.evidence, r.notes,
                ai.item_code, ai.indicator, ai.target, ai.statement,
                s.id AS standard_id, s.code AS standard_code, s.name AS standard_name,
                u.id AS unit_id, u.name AS auditee_name,
                p.id AS period_id, p.period_name,
                a.assignment_number,
                (SELECT COUNT(*) FROM rtm_action_plans WHERE checklist_result_id = r.id) AS total_rtl
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            JOIN units u ON u.id = a.auditee_id
            LEFT JOIN audit_periods p ON p.id = a.period_id
        ";
    }

    private function applyFilters(string $sql, array &$params, string &$types, string $search, int $periodId, int $unitId, string $status): string
    {
        $sql .= " WHERE r.audit_status IS NOT NULL AND r.audit_status <> '' ";

        if ($periodId > 0) {
            $sql .= " AND p.id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($unitId > 0) {
            $sql .= " AND u.id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($status !== '') {
            $sql .= " AND r.audit_status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (ai.item_code LIKE ? OR ai.indicator LIKE ? OR u.name LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "sss";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        return $sql;
    }

    public function getAll(string $search = '', int $periodId = 0, int $unitId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        $types = '';
        $params = [];

        $sql = $this->applyFilters($this->baseSelect(), $params, $types, $search, $periodId, $unitId, $status);

        $sql .= " ORDER BY r.audited_at DESC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function count(string $search = '', int $periodId = 0, int $unitId = 0, string $status = ''): int
    {
        $types = '';
        $params = [];

        $sql = $this->applyFilters("SELECT COUNT(*) AS total FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            JOIN units u ON u.id = a.auditee_id
            LEFT JOIN audit_periods p ON p.id = a.period_id
        ", $params, $types, $search, $periodId, $unitId, $status);

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
        $sql = $this->baseSelect() . " WHERE r.id = ? LIMIT 1 ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

public function getStatistics(int $unitId = 0): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            WHERE r.audit_status IS NOT NULL AND r.audit_status <> ''
        ";

        $types = '';
        $params = [];

        if ($unitId > 0) {
            $sql .= " AND a.auditee_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return [
            'total'            => (int)($row['total'] ?? 0),
            'tidak_terpenuhi'  => (int)($row['tidak_terpenuhi'] ?? 0),
            'sebagian'         => (int)($row['sebagian'] ?? 0),
            'memenuhi'         => (int)($row['memenuhi'] ?? 0),
            'melampaui'        => (int)($row['melampaui'] ?? 0),
        ];
    }
}