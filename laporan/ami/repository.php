<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class AmiReportRepository extends BaseRepository
{
    protected string $table = 'audit_assignments';

    public function getUnitInfo(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT id, code, name, head_name FROM units WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

        private function getKategoriCapaian(?float $skor): string
    {
        if ($skor === null) return '-';
        if ($skor < 2) return 'Sangat Kurang Baik';
        if ($skor < 3) return 'Kurang Baik';
        if ($skor < 4) return 'Baik';
        return 'Sangat Baik';
    }

    public function getStandardAchievementSummary(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                s.id AS standard_id, s.code AS standard_code, s.name AS standard_name,
                COUNT(*) AS total,
                SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN standards s ON s.id = c.standard_id
            WHERE a.auditee_id = ? AND a.period_id = ?
              AND r.audit_status IS NOT NULL AND r.audit_status <> ''
            GROUP BY s.id, s.code, s.name
            ORDER BY s.code ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {

            $total = (int) $row['total'];

            $skor = $total > 0
                ? round((
                    (int) $row['tidak_terpenuhi'] * 1
                    + (int) $row['sebagian'] * 2
                    + (int) $row['memenuhi'] * 3
                    + (int) $row['melampaui'] * 4
                ) / $total, 2)
                : null;

            $row['skor_capaian'] = $skor;
            $row['kategori_capaian'] = $this->getKategoriCapaian($skor);
        }

        return $rows;
    }

    public function getPeriodInfo(int $periodId): ?array
    {
        $stmt = $this->prepare("SELECT id, period_name, year, academic_year, start_date, end_date FROM audit_periods WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $periodId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getKetuaLpm(): ?array
    {
        $stmt = $this->prepare("SELECT full_name FROM users WHERE role_id = 2 LIMIT 1");
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getAssignments(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                a.id, a.assignment_number, a.audit_type, a.audit_date, a.status,
                us.full_name AS lead_auditor_name
            FROM audit_assignments a
            LEFT JOIN users us ON us.id = a.lead_auditor
            WHERE a.auditee_id = ? AND a.period_id = ?
            ORDER BY a.audit_date ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAuditedStandards(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT DISTINCT s.id, s.code, s.name
            FROM audit_assignments a
            JOIN assignment_standards ast ON ast.assignment_id = a.id
            JOIN standards s ON s.id = ast.standard_id
            WHERE a.auditee_id = ? AND a.period_id = ?
            ORDER BY s.code ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getFindings(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                r.audit_status, r.achievement, r.finding, r.root_cause,
                r.supporting_factor, r.recommendation,
                r.document_audit_result, r.field_audit_result, r.evidence, r.notes,
                ai.item_code, ai.statement, ai.indicator, ai.target,
                s.code AS standard_code, s.name AS standard_name
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            WHERE a.auditee_id = ? AND a.period_id = ?
              AND r.audit_status IS NOT NULL AND r.audit_status <> ''
            ORDER BY s.code ASC, ai.item_code ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getFindingStatistics(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            WHERE a.auditee_id = ? AND a.period_id = ?
              AND r.audit_status IS NOT NULL AND r.audit_status <> ''
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return [
            'total'           => (int)($row['total'] ?? 0),
            'tidak_terpenuhi' => (int)($row['tidak_terpenuhi'] ?? 0),
            'sebagian'        => (int)($row['sebagian'] ?? 0),
            'memenuhi'        => (int)($row['memenuhi'] ?? 0),
            'melampaui'       => (int)($row['melampaui'] ?? 0),
        ];
    }

    public function getActionPlans(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                ap.activity, ap.implementation_time, ap.pic, ap.budget, ap.status,
                ap.importance, ap.urgency,
                ai.item_code, ai.indicator
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
            JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            WHERE m.unit_id = ? AND m.period_id = ?
            ORDER BY ap.created_at ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getTeamMembers(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT DISTINCT tm.user_id, tm.role_in_team, us.full_name
            FROM audit_team_members tm
            JOIN users us ON us.id = tm.user_id
            JOIN audit_assignments a ON a.id = tm.assignment_id
            WHERE a.auditee_id = ? AND a.period_id = ? AND tm.status = 'Aktif'
            ORDER BY FIELD(tm.role_in_team, 'Ketua', 'Auditor', 'Observer'), us.full_name ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}