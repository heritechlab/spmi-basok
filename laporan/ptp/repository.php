<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class PtpReportRepository extends BaseRepository
{
    protected string $table = 'ptp_meetings';

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

    public function getKetuaLpm(): ?array
    {
        $stmt = $this->prepare("SELECT full_name FROM users WHERE role_id = 2 LIMIT 1");
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

public function getMeetings(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT id, meeting_number, meeting_date, venue, start_time, end_time, agenda, minutes, status
            FROM ptp_meetings
            WHERE unit_id = ? AND period_id = ? AND status = 'Selesai'
            ORDER BY meeting_date ASC
        ");

        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getParticipants(int $meetingId): array
    {
        $stmt = $this->prepare("SELECT full_name, position FROM ptp_participants WHERE ptp_meeting_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $meetingId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getItems(int $meetingId): array
    {
        $stmt = $this->prepare("
            SELECT pi.*, s.code AS standard_code, s.name AS standard_name
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
}