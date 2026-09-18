<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class RtmReportRepository extends BaseRepository
{
    protected string $table = 'rtm_meetings';

    public function getUnitInfo(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT id, code, name, head_name FROM units WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }
    public function getDetailedActionPlans(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                ap.id, ap.activity, ap.implementation_time, ap.pic, ap.budget, ap.status,
                ap.importance, ap.urgency, ap.source_type,
                r.finding, r.root_cause, r.recommendation, r.audit_status,
                ai.item_code, ai.indicator,
                s.code AS standard_code, s.name AS standard_name,
                m.meeting_number,
                sc.name AS survey_category_name,
                st.name AS survey_type_name
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
            LEFT JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            LEFT JOIN audit_checklists c ON c.id = r.checklist_id
            LEFT JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            LEFT JOIN standards s ON s.id = c.standard_id
            LEFT JOIN survey_categories sc ON sc.id = ap.survey_category_id
            LEFT JOIN survey_types st ON st.id = ap.survey_type_id
            WHERE m.unit_id = ? AND m.period_id = ?
            ORDER BY s.code ASC, ai.item_code ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAllDocuments(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT d.document_type, d.document_file, d.document_original_name, m.meeting_number
            FROM rtm_documents d
            JOIN rtm_meetings m ON m.id = d.rtm_meeting_id
            WHERE m.unit_id = ? AND m.period_id = ?
            ORDER BY d.document_type ASC, d.id ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getPeriodInfo(int $periodId): ?array
    {
        $stmt = $this->prepare("SELECT id, period_name, year FROM audit_periods WHERE id = ? LIMIT 1");
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

    public function getMeetings(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT id, meeting_number, meeting_date, agenda, minutes, status,
                   notulis_nama, notulis_ttd, notulis_tanggal,
                   pimpinan_nama, pimpinan_ttd, pimpinan_tanggal
            FROM rtm_meetings
            WHERE unit_id = ? AND period_id = ? AND meeting_number NOT LIKE 'RTM-SURVEY-%'
            ORDER BY meeting_date ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getDocuments(int $meetingId): array
    {
        $stmt = $this->prepare("SELECT document_type, document_original_name FROM rtm_documents WHERE rtm_meeting_id = ? ORDER BY document_type ASC");
        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getActionPlans(int $meetingId): array
    {
        $stmt = $this->prepare("
            SELECT
                ap.activity, ap.implementation_time, ap.pic, ap.budget, ap.status,
                ap.importance, ap.urgency, ap.source_type,
                ai.item_code, ai.indicator,
                sc.name AS survey_category_name
            FROM rtm_action_plans ap
            LEFT JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            LEFT JOIN audit_checklists c ON c.id = r.checklist_id
            LEFT JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            LEFT JOIN survey_categories sc ON sc.id = ap.survey_category_id
            WHERE ap.rtm_meeting_id = ?
            ORDER BY ap.created_at ASC
        ");

        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}