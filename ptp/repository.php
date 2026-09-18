<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class PtpRepository extends BaseRepository
{
    protected string $table = 'ptp_meetings';

    /*
    |--------------------------------------------------------------------------
    | Info Pendukung
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Indikator yang Mencapai/Melampaui (bahan usulan PTP)
    |--------------------------------------------------------------------------
    */

    public function getEligibleItems(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT DISTINCT
                ai.id AS audit_indicator_id,
                r.id AS checklist_result_id,
                ai.item_code, ai.statement, ai.indicator, ai.target,
                s.code AS standard_code, s.name AS standard_name,
                r.audit_status, r.achievement
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            WHERE a.auditee_id = ? AND a.period_id = ?
              AND r.audit_status IN ('Mencapai', 'Melampaui')
            ORDER BY s.code ASC, ai.item_code ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | Get All / Count / Statistics
    |--------------------------------------------------------------------------
    */

public function getAll(int $unitId, int $periodId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        $sql = "
            SELECT
                m.id, m.meeting_number, m.meeting_date, m.status,
                m.unit_id, m.period_id,
                p.period_name,
                (SELECT COUNT(*) FROM ptp_items WHERE ptp_meeting_id = m.id) AS total_items
            FROM ptp_meetings m
            LEFT JOIN audit_periods p ON p.id = m.period_id
            WHERE 1 = 1
        ";

        $types = "";
        $params = [];

        if ($unitId > 0) {
            $sql .= " AND m.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($periodId > 0) {
            $sql .= " AND m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($status !== '') {
            $sql .= " AND m.status = ? ";
            $types .= "s";
            $params[] = $status;
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

public function count(int $unitId, int $periodId = 0, string $status = ''): int
    {
        $sql = "SELECT COUNT(*) AS total FROM ptp_meetings m WHERE 1 = 1";

        $types = "";
        $params = [];

        if ($unitId > 0) {
            $sql .= " AND m.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($periodId > 0) {
            $sql .= " AND m.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($status !== '') {
            $sql .= " AND m.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

$stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

    public function getStatistics(int $unitId): array
    {
        return [
            'total'   => $this->count($unitId),
            'draft'   => $this->count($unitId, 0, 'Draft'),
            'selesai' => $this->count($unitId, 0, 'Selesai'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Find / Exists
    |--------------------------------------------------------------------------
    */

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("
            SELECT m.*, p.period_name, u.name AS unit_name
            FROM ptp_meetings m
            LEFT JOIN audit_periods p ON p.id = m.period_id
            LEFT JOIN units u ON u.id = m.unit_id
            WHERE m.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function existsNumber(string $number, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM ptp_meetings WHERE meeting_number = ?";

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
            INSERT INTO ptp_meetings
                (period_id, unit_id, meeting_number, meeting_date, venue, start_time, end_time, agenda, minutes, status, created_by, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iissssssssi",
            $data['period_id'],
            $data['unit_id'],
            $data['meeting_number'],
            $data['meeting_date'],
            $data['venue'],
            $data['start_time'],
            $data['end_time'],
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
            UPDATE ptp_meetings
            SET period_id = ?, unit_id = ?, meeting_number = ?, meeting_date = ?, venue = ?,
                start_time = ?, end_time = ?, agenda = ?, minutes = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iissssssssi",
            $data['period_id'],
            $data['unit_id'],
            $data['meeting_number'],
            $data['meeting_date'],
            $data['venue'],
            $data['start_time'],
            $data['end_time'],
            $data['agenda'],
            $data['minutes'],
            $data['status'],
            $id
        );

        $this->execute($stmt);

        return true;
    }

    public function findMeetingByNumber(string $meetingNumber): ?array
    {
        $stmt = $this->prepare("SELECT id FROM ptp_meetings WHERE meeting_number = ? LIMIT 1");
        $stmt->bind_param("s", $meetingNumber);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function findOrCreateSurveyMeeting(int $unitId, int $periodId, int $surveyYear, int $createdBy): int
    {
        $meetingNumber = 'PTP-SURVEY-' . $surveyYear . '-U' . $unitId;

        $existing = $this->findMeetingByNumber($meetingNumber);

        if ($existing) {
            return (int) $existing['id'];
        }

        $agenda = 'Peningkatan Berdasarkan Hasil Survey Kepuasan Tahun ' . $surveyYear;

        return $this->create([
            'period_id'      => $periodId,
            'unit_id'        => $unitId,
            'meeting_number' => $meetingNumber,
            'meeting_date'   => date('Y-m-d'),
            'venue'          => null,
            'start_time'     => null,
            'end_time'       => null,
            'agenda'         => $agenda,
            'minutes'        => '',
            'status'         => 'Draft',
            'created_by'     => $createdBy,
        ]);
    }

    public function existsSurveyItem(int $meetingId, int $surveyCategoryId, int $surveyYear): bool
    {
        $stmt = $this->prepare("
            SELECT id FROM ptp_items
            WHERE ptp_meeting_id = ? AND survey_category_id = ? AND survey_year = ? AND source_type = 'survey'
            LIMIT 1
        ");
        $stmt->bind_param("iii", $meetingId, $surveyCategoryId, $surveyYear);
        $this->execute($stmt);

        return (bool) $this->fetchOne($stmt);
    }

    public function createItemFromSurvey(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO ptp_items
                (ptp_meeting_id, source_type, survey_type_id, survey_category_id, survey_year, description, status, created_at)
            VALUES (?, 'survey', ?, ?, ?, ?, 'Diusulkan', NOW())
        ");
        $stmt->bind_param(
            "iiiis",
            $data['ptp_meeting_id'],
            $data['survey_type_id'],
            $data['survey_category_id'],
            $data['survey_year'],
            $data['description']
        );
        $this->execute($stmt);

        return $this->insertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Peserta Rapat
    |--------------------------------------------------------------------------
    */

    public function getParticipants(int $meetingId): array
    {
        $stmt = $this->prepare("SELECT * FROM ptp_participants WHERE ptp_meeting_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveParticipants(int $meetingId, array $participants): void
    {
        $delete = $this->prepare("DELETE FROM ptp_participants WHERE ptp_meeting_id = ?");
        $delete->bind_param("i", $meetingId);
        $this->execute($delete);

        if (empty($participants)) {
            return;
        }

        $insert = $this->prepare("
            INSERT INTO ptp_participants (ptp_meeting_id, user_id, full_name, position, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        foreach ($participants as $p) {
            $userId = !empty($p['user_id']) ? (int) $p['user_id'] : null;
            $fullName = $p['full_name'];
            $position = $p['position'];
            $insert->bind_param("iiss", $meetingId, $userId, $fullName, $position);
            $this->execute($insert);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Item PTP (Indikator yang Diusulkan Naik)
    |--------------------------------------------------------------------------
    */

    public function getItems(int $meetingId): array
    {
        $stmt = $this->prepare("
            SELECT pi.*, s.code AS standard_code
            FROM ptp_items pi
            LEFT JOIN audit_indicators ai ON ai.id = pi.audit_indicator_id
            LEFT JOIN standards s ON s.id = ai.standard_id
            WHERE pi.ptp_meeting_id = ?
            ORDER BY pi.id ASC
        ");

        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findItemById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM ptp_items WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function saveItem(array $data): int
    {
        $sql = "
            INSERT INTO ptp_items
                (ptp_meeting_id, audit_indicator_id, checklist_result_id,
                 old_indicator, new_indicator, old_statement, new_statement,
                 old_target, new_target, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Diusulkan', NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iiissssss",
            $data['ptp_meeting_id'],
            $data['audit_indicator_id'],
            $data['checklist_result_id'],
            $data['old_indicator'],
            $data['new_indicator'],
            $data['old_statement'],
            $data['new_statement'],
            $data['old_target'],
            $data['new_target']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function deleteItem(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM ptp_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function applyItem(int $id): bool
    {
        $item = $this->findItemById($id);

        if (!$item) {
            return false;
        }

        $stmt = $this->prepare("
            UPDATE audit_indicators
            SET indicator = ?, statement = ?, target = ?
            WHERE id = ?
        ");

        $indicator = $item['new_indicator'];
        $statement = $item['new_statement'];
        $target = $item['new_target'];
        $indicatorId = (int) $item['audit_indicator_id'];

        $stmt->bind_param("sssi", $indicator, $statement, $target, $indicatorId);
        $this->execute($stmt);

        $update = $this->prepare("UPDATE ptp_items SET status = 'Ditingkatkan', applied_at = NOW() WHERE id = ?");
        $update->bind_param("i", $id);
        $this->execute($update);

        return true;
    }
}