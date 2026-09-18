<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class RtlReportRepository extends BaseRepository
{
    protected string $table = 'rtm_action_plan_evidences';

    public function getUnitInfo(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT id, code, name, head_name FROM units WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getPeriodInfo(int $periodId): ?array
    {
        $stmt = $this->prepare("SELECT id, period_name, year FROM audit_periods WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $periodId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getEvidenceList(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                e.id, e.document_file, e.document_original_name, e.uploaded_at,
                ap.activity, ap.status, ap.implementation_time, ap.pic,
                ai.item_code, ai.indicator,
                us.full_name AS uploaded_by_name
            FROM rtm_action_plan_evidences e
            JOIN rtm_action_plans ap ON ap.id = e.rtm_action_plan_id
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
            JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            LEFT JOIN users us ON us.id = e.uploaded_by
            WHERE m.unit_id = ? AND m.period_id = ?
            ORDER BY e.uploaded_at ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}