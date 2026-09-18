<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class RtmRepository extends BaseRepository
{
    protected string $table = 'rtm_meetings';

    /*
    |--------------------------------------------------------------------------
    | Get All
    |--------------------------------------------------------------------------
    */

    public function getAll(string $search = '', int $periodId = 0, string $status = '', int $unitId = 0, int $limit = 10, int $offset = 0): array
    {
        $sql = "
            SELECT
                m.id, m.meeting_number, m.meeting_date, m.status,
                m.unit_id, m.period_id,
                p.period_name,
                (SELECT COUNT(*) FROM rtm_action_plans WHERE rtm_meeting_id = m.id) AS total_rtl
            FROM rtm_meetings m
            LEFT JOIN audit_periods p ON p.id = m.period_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if ($periodId > 0) {
            $sql .= " AND m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($unitId > 0) {
            $sql .= " AND m.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($status !== '') {
            $sql .= " AND m.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (m.meeting_number LIKE ? OR m.agenda LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "ss";
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY m.meeting_date DESC, m.id DESC LIMIT ? OFFSET ? ";

        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    /*
    |--------------------------------------------------------------------------
    | Pelaksanaan RTL
    |--------------------------------------------------------------------------
    */

    public function findActionPlanDetail(int $id): ?array
    {
        $stmt = $this->prepare("
            SELECT
                ap.*,
                m.meeting_number, m.unit_id,
                ai.item_code, ai.indicator, ai.target,
                s.code AS standard_code, s.name AS standard_name,
                u.name AS auditee_name
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
            JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            LEFT JOIN units u ON u.id = m.unit_id
            WHERE ap.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function updateImplementation(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE rtm_action_plans
            SET progress_note = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("ssi", $data['progress_note'], $data['status'], $id);
        $this->execute($stmt);

        return true;
    }

    public function verifyActionPlan(int $id, string $verificationStatus, string $verificationNote, int $verifiedBy): bool
    {
        $stmt = $this->prepare("
            UPDATE rtm_action_plans
            SET verification_status = ?, verification_note = ?, verified_by = ?, verified_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("ssii", $verificationStatus, $verificationNote, $verifiedBy, $id);
        $this->execute($stmt);

        return true;
    }

    public function getEvidences(int $actionPlanId): array
    {
        $stmt = $this->prepare("SELECT * FROM rtm_action_plan_evidences WHERE rtm_action_plan_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $actionPlanId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function saveEvidence(int $actionPlanId, array $data, int $uploadedBy): int
    {
        $stmt = $this->prepare("
            INSERT INTO rtm_action_plan_evidences (rtm_action_plan_id, document_file, document_original_name, document_size, link_url, uploaded_by, uploaded_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "issisi",
            $actionPlanId,
            $data['document_file'],
            $data['document_original_name'],
            $data['document_size'],
            $data['link_url'],
            $uploadedBy
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function deleteEvidence(int $evidenceId): bool
    {
        $stmt = $this->prepare("DELETE FROM rtm_action_plan_evidences WHERE id = ?");
        $stmt->bind_param("i", $evidenceId);
        $this->execute($stmt);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Penetapan RTL (Rekap Semua RTL Lintas Rapat)
    |--------------------------------------------------------------------------
    */

    private function actionPlanBaseSelect(): string
    {
        return "
            SELECT
                ap.id, ap.importance, ap.urgency, ap.activity, ap.implementation_time,
                ap.pic, ap.budget, ap.status, ap.verification_status, ap.created_at,
                m.id AS rtm_meeting_id, m.meeting_number,
                r.audit_status,
                ai.item_code, ai.indicator,
                s.code AS standard_code,
                u.id AS unit_id, u.name AS auditee_name,
                p.period_name
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
            JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            LEFT JOIN units u ON u.id = m.unit_id
            LEFT JOIN audit_periods p ON p.id = m.period_id
        ";
    }

    private function applyActionPlanFilters(string $sql, array &$params, string &$types, int $periodId, int $unitId, string $status): string
    {
        $sql .= " WHERE 1 = 1 ";

        if ($periodId > 0) {
            $sql .= " AND m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($unitId > 0) {
            $sql .= " AND m.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($status !== '') {
            $sql .= " AND ap.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        return $sql;
    }

    public function getAllActionPlans(int $periodId = 0, int $unitId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        $types = '';
        $params = [];

        $sql = $this->applyActionPlanFilters($this->actionPlanBaseSelect(), $params, $types, $periodId, $unitId, $status);

        $sql .= " ORDER BY ap.created_at DESC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function countAllActionPlans(int $periodId = 0, int $unitId = 0, string $status = ''): int
    {
        $types = '';
        $params = [];

        $sql = $this->applyActionPlanFilters("
            SELECT COUNT(*) AS total
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
        ", $params, $types, $periodId, $unitId, $status);

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

    public function getActionPlanStatistics(int $unitId = 0): array
    {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN ap.status = 'Belum' THEN 1 ELSE 0 END) AS belum,
                SUM(CASE WHEN ap.status = 'Proses' THEN 1 ELSE 0 END) AS proses,
                SUM(CASE WHEN ap.status = 'Selesai' THEN 1 ELSE 0 END) AS selesai
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if ($unitId > 0) {
            $sql .= " AND m.unit_id = ? ";
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
            'total'   => (int)($row['total'] ?? 0),
            'belum'   => (int)($row['belum'] ?? 0),
            'proses'  => (int)($row['proses'] ?? 0),
            'selesai' => (int)($row['selesai'] ?? 0),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Count
    |--------------------------------------------------------------------------
    */

    public function count(string $search = '', int $periodId = 0, string $status = '', int $unitId = 0): int
    {
        $sql = "SELECT COUNT(*) AS total FROM rtm_meetings m WHERE 1 = 1";

        $types = '';
        $params = [];

        if ($periodId > 0) {
            $sql .= " AND m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($unitId > 0) {
            $sql .= " AND m.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($status !== '') {
            $sql .= " AND m.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (m.meeting_number LIKE ? OR m.agenda LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "ss";
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    public function getStatistics(): array
    {
        return [
            'total'   => $this->count(),
            'draft'   => $this->count('', 0, 'Draft'),
            'selesai' => $this->count('', 0, 'Selesai'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Find By Id
    |--------------------------------------------------------------------------
    */

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("
            SELECT m.*, p.period_name
            FROM rtm_meetings m
            LEFT JOIN audit_periods p ON p.id = m.period_id
            WHERE m.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Exists Number
    |--------------------------------------------------------------------------
    */

    public function existsNumber(string $number, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM rtm_meetings WHERE meeting_number = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("si", $number, $excludeId);
        } else {
            $stmt->bind_param("s", $number);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return ((int)($row['total'] ?? 0)) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Create / Update
    |--------------------------------------------------------------------------
    */

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO rtm_meetings
                (period_id, unit_id, meeting_number, meeting_date, agenda, minutes, status, created_by, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iisssssi",
            $data['period_id'],
            $data['unit_id'],
            $data['meeting_number'],
            $data['meeting_date'],
            $data['agenda'],
            $data['minutes'],
            $data['status'],
            $data['created_by']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE rtm_meetings
            SET period_id = ?, meeting_number = ?, meeting_date = ?, agenda = ?, minutes = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "isssssi",
            $data['period_id'],
            $data['meeting_number'],
            $data['meeting_date'],
            $data['agenda'],
            $data['minutes'],
            $data['status'],
            $id
        );

        $this->execute($stmt);

        return true;
    }

    public function updateBeritaAcaraSignature(int $meetingId, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE rtm_meetings
            SET notulis_nama = ?, notulis_ttd = ?, notulis_tanggal = ?,
                pimpinan_nama = ?, pimpinan_ttd = ?, pimpinan_tanggal = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssi",
            $data['notulis_nama'],
            $data['notulis_ttd'],
            $data['notulis_tanggal'],
            $data['pimpinan_nama'],
            $data['pimpinan_ttd'],
            $data['pimpinan_tanggal'],
            $meetingId
        );

        $this->execute($stmt);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Dokumen (BAP / Notulen / Foto)
    |--------------------------------------------------------------------------
    */

    public function getDocuments(int $meetingId): array
    {
        $stmt = $this->prepare("SELECT * FROM rtm_documents WHERE rtm_meeting_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveDocument(int $meetingId, string $type, array $uploaded): int
    {
        $stmt = $this->prepare("
            INSERT INTO rtm_documents (rtm_meeting_id, document_type, document_file, document_original_name, document_size, uploaded_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "isssi",
            $meetingId,
            $type,
            $uploaded['document_file'],
            $uploaded['document_original_name'],
            $uploaded['document_size']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function deleteDocument(int $docId): bool
    {
        $stmt = $this->prepare("DELETE FROM rtm_documents WHERE id = ?");
        $stmt->bind_param("i", $docId);
        $this->execute($stmt);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Temuan yang tersedia untuk dipilih (per Periode)
    |--------------------------------------------------------------------------
    */

    public function getAvailableFindings(int $periodId, int $unitId = 0): array
    {
        $sql = "
            SELECT
                r.id AS checklist_result_id,
                r.audit_status, r.finding, r.root_cause, r.supporting_factor, r.recommendation,
                ai.item_code, ai.indicator,
                s.code AS standard_code, s.name AS standard_name,
                u.name AS auditee_name,
                a.assignment_number
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            JOIN units u ON u.id = a.auditee_id
            WHERE a.period_id = ?
              AND r.audit_status IN ('Menyimpang', 'Belum Mencapai')
        ";

        $types = "i";
        $params = [$periodId];

        if ($unitId > 0) {
            $sql .= " AND a.auditee_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " ORDER BY u.name ASC, s.code ASC, ai.item_code ASC ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | RTL (Action Plans)
    |--------------------------------------------------------------------------
    */

    public function getActionPlans(int $meetingId): array
    {
        $stmt = $this->prepare("
            SELECT
                ap.*,
                r.audit_status, r.finding,
                ai.item_code, ai.indicator,
                s.code AS standard_code,
                u.name AS auditee_name,
                sc.name AS survey_category_name,
                st.name AS survey_type_name
            FROM rtm_action_plans ap
            LEFT JOIN audit_checklist_results r ON r.id = ap.checklist_result_id
            LEFT JOIN audit_checklists c ON c.id = r.checklist_id
            LEFT JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            LEFT JOIN standards s ON s.id = c.standard_id
            LEFT JOIN audit_assignments a ON a.id = c.assignment_id
            LEFT JOIN units u ON u.id = a.auditee_id
            LEFT JOIN survey_categories sc ON sc.id = ap.survey_category_id
            LEFT JOIN survey_types st ON st.id = ap.survey_type_id
            WHERE ap.rtm_meeting_id = ?
            ORDER BY ap.id ASC
        ");

        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findActionPlanById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM rtm_action_plans WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function saveActionPlan(array $data): int
    {
        $sql = "
            INSERT INTO rtm_action_plans
                (rtm_meeting_id, checklist_result_id, importance, urgency, activity, implementation_time, pic, budget, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iisssssss",
            $data['rtm_meeting_id'],
            $data['checklist_result_id'],
            $data['importance'],
            $data['urgency'],
            $data['activity'],
            $data['implementation_time'],
            $data['pic'],
            $data['budget'],
            $data['status']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function updateActionPlan(int $id, array $data): bool
    {
        $sql = "
            UPDATE rtm_action_plans
            SET checklist_result_id = ?, importance = ?, urgency = ?, activity = ?,
                implementation_time = ?, pic = ?, budget = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "isssssssi",
            $data['checklist_result_id'],
            $data['importance'],
            $data['urgency'],
            $data['activity'],
            $data['implementation_time'],
            $data['pic'],
            $data['budget'],
            $data['status'],
            $id
        );

        $this->execute($stmt);

        return true;
    }

    public function deleteActionPlan(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM rtm_action_plans WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
    /*
    |--------------------------------------------------------------------------
    | Monitoring RTL per Unit Kerja
    |--------------------------------------------------------------------------
    */

    public function getMonitoringByUnit(int $periodId = 0): array
    {
        $sql = "
            SELECT
                u.id AS unit_id, u.name AS unit_name,
                COUNT(ap.id) AS total,
                SUM(CASE WHEN ap.status = 'Belum' THEN 1 ELSE 0 END) AS belum,
                SUM(CASE WHEN ap.status = 'Proses' THEN 1 ELSE 0 END) AS proses,
                SUM(CASE WHEN ap.status = 'Selesai' THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN ap.verification_status = 'Sesuai' THEN 1 ELSE 0 END) AS verif_sesuai,
                SUM(CASE WHEN ap.verification_status = 'Perlu Revisi' THEN 1 ELSE 0 END) AS verif_revisi,
                SUM(CASE WHEN ap.verification_status IS NULL OR ap.verification_status = 'Belum Diverifikasi' THEN 1 ELSE 0 END) AS verif_belum
            FROM units u
            JOIN rtm_meetings m ON m.unit_id = u.id
            JOIN rtm_action_plans ap ON ap.rtm_meeting_id = m.id
        ";

        $types = '';
        $params = [];

        if ($periodId > 0) {
            $sql .= " WHERE m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        $sql .= " GROUP BY u.id, u.name ORDER BY u.name ASC ";

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMonitoringOverall(int $periodId = 0): array
    {
        $sql = "
            SELECT
                COUNT(ap.id) AS total,
                SUM(CASE WHEN ap.status = 'Belum' THEN 1 ELSE 0 END) AS belum,
                SUM(CASE WHEN ap.status = 'Proses' THEN 1 ELSE 0 END) AS proses,
                SUM(CASE WHEN ap.status = 'Selesai' THEN 1 ELSE 0 END) AS selesai
            FROM rtm_action_plans ap
            JOIN rtm_meetings m ON m.id = ap.rtm_meeting_id
        ";

        $types = '';
        $params = [];

        if ($periodId > 0) {
            $sql .= " WHERE m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return [
            'total'   => (int)($row['total'] ?? 0),
            'belum'   => (int)($row['belum'] ?? 0),
            'proses'  => (int)($row['proses'] ?? 0),
            'selesai' => (int)($row['selesai'] ?? 0),
        ];
    }

    public function findOrCreateSurveyMeeting(int $unitId, int $periodId, int $surveyYear, int $createdBy): int
    {
        $meetingNumber = 'RTM-SURVEY-' . $surveyYear . '-U' . $unitId;

        $stmt = $this->prepare("SELECT id FROM rtm_meetings WHERE meeting_number = ? LIMIT 1");
        $stmt->bind_param("s", $meetingNumber);
        $this->execute($stmt);
        $existing = $this->fetchOne($stmt);

        if ($existing) {
            return (int) $existing['id'];
        }

        $agenda = 'Tindak Lanjut Hasil Survey Kepuasan Tahun ' . $surveyYear;

        $stmt2 = $this->prepare("
            INSERT INTO rtm_meetings (period_id, unit_id, meeting_number, meeting_date, agenda, minutes, status, created_by, created_at)
            VALUES (?, ?, ?, CURDATE(), ?, '', 'Draft', ?, NOW())
        ");
        $stmt2->bind_param("iissi", $periodId, $unitId, $meetingNumber, $agenda, $createdBy);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function existsSurveyActionPlan(int $meetingId, int $surveyCategoryId, int $surveyYear): bool
    {
        $stmt = $this->prepare("
            SELECT id FROM rtm_action_plans
            WHERE rtm_meeting_id = ? AND survey_category_id = ? AND survey_year = ? AND source_type = 'survey'
            LIMIT 1
        ");
        $stmt->bind_param("iii", $meetingId, $surveyCategoryId, $surveyYear);
        $this->execute($stmt);

        return (bool) $this->fetchOne($stmt);
    }

    public function createActionPlanFromSurvey(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO rtm_action_plans
                (rtm_meeting_id, source_type, survey_type_id, survey_category_id, survey_year, importance, urgency, activity, status, created_at)
            VALUES (?, 'survey', ?, ?, ?, ?, ?, ?, 'Belum', NOW())
        ");
        $stmt->bind_param(
            "iiiisss",
            $data['rtm_meeting_id'],
            $data['survey_type_id'],
            $data['survey_category_id'],
            $data['survey_year'],
            $data['importance'],
            $data['urgency'],
            $data['activity']
        );
        $this->execute($stmt);

        return $this->insertId();
    }
}