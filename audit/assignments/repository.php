<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class AssignmentRepository extends BaseRepository
{
    protected string $table = 'audit_assignments';

    /*
    |--------------------------------------------------------------------------
    | Get All
    |--------------------------------------------------------------------------
    */

    public function getAll(
        string $search = '',
        int $periodId = 0,
        string $status = '',
        int $limit = 10,
        int $offset = 0
    ): array {

        $sql = "
            SELECT
                a.id, a.assignment_number, a.audit_type, a.audit_date, a.status,
                a.period_id, a.auditee_id, a.lead_auditor,
                a.document_file, a.document_original_name,
                a.approved, a.approved_at,
                p.period_name,
                u.name AS auditee_name,
                u.head_name AS auditee_head,
                us.full_name AS lead_auditor_name
            FROM audit_assignments a
            LEFT JOIN audit_periods p ON p.id = a.period_id
            LEFT JOIN units u ON u.id = a.auditee_id
            LEFT JOIN users us ON us.id = a.lead_auditor
            WHERE 1 = 1
        ";

        $types  = '';
        $params = [];

        if ($periodId > 0) {
            $sql .= " AND a.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($status !== '') {
            $sql .= " AND a.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (a.assignment_number LIKE ? OR u.name LIKE ? OR us.full_name LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "sss";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY a.audit_date DESC, a.id DESC LIMIT ? OFFSET ? ";

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
    | Count
    |--------------------------------------------------------------------------
    */

    public function count(string $search = '', int $periodId = 0, string $status = ''): int
    {

        $sql = "
            SELECT COUNT(*) AS total
            FROM audit_assignments a
            LEFT JOIN units u ON u.id = a.auditee_id
            LEFT JOIN users us ON us.id = a.lead_auditor
            WHERE 1 = 1
        ";

        $types  = '';
        $params = [];

        if ($periodId > 0) {
            $sql .= " AND a.period_id = ? ";
            $types .= "i";
            $params[] = $periodId;
        }

        if ($status !== '') {
            $sql .= " AND a.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (a.assignment_number LIKE ? OR u.name LIKE ? OR us.full_name LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "sss";
            $params[] = $keyword;
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
            'total'       => $this->count(),
            'draft'       => $this->count('', 0, 'Draft'),
            'dijadwalkan' => $this->count('', 0, 'Dijadwalkan'),
            'berlangsung' => $this->count('', 0, 'Berlangsung'),
            'selesai'     => $this->count('', 0, 'Selesai'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Find By ID
    |--------------------------------------------------------------------------
    */

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("
            SELECT
                a.*,
                p.period_name,
                u.name AS auditee_name,
                u.head_name AS auditee_head,
                us.full_name AS lead_auditor_name
            FROM audit_assignments a
            LEFT JOIN audit_periods p ON p.id = a.period_id
            LEFT JOIN units u ON u.id = a.auditee_id
            LEFT JOIN users us ON us.id = a.lead_auditor
            WHERE a.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function approve(int $id, int $approvedBy): bool
    {
        $stmt = $this->prepare("
            UPDATE audit_assignments
            SET approved = 1, approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $approvedBy, $id);

        $this->execute($stmt);

        return true;
    }

    public function getUnitContact(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT name, head_name, email FROM units WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Exists Assignment Number
    |--------------------------------------------------------------------------
    */

    public function existsNumber(string $number, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM audit_assignments WHERE assignment_number = ?";

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

    public function getTeamMembers(int $assignmentId): array
    {
        $stmt = $this->prepare("
            SELECT tm.id, tm.user_id, tm.role_in_team, us.full_name
            FROM audit_team_members tm
            JOIN users us ON us.id = tm.user_id
            WHERE tm.assignment_id = ? AND tm.status = 'Aktif'
            ORDER BY FIELD(tm.role_in_team, 'Ketua', 'Auditor', 'Observer'), us.full_name ASC
        ");

        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveTeamMembers(int $assignmentId, array $members): void
    {
        $delete = $this->prepare("DELETE FROM audit_team_members WHERE assignment_id = ?");
        $delete->bind_param("i", $assignmentId);
        $this->execute($delete);

        if (empty($members)) {
            return;
        }

        $insert = $this->prepare("
            INSERT INTO audit_team_members (assignment_id, user_id, role_in_team, status, joined_at, created_at)
            VALUES (?, ?, ?, 'Aktif', NOW(), NOW())
        ");

        foreach ($members as $member) {
            $userId = (int) $member['user_id'];
            $role = $member['role_in_team'];
            $insert->bind_param("iis", $assignmentId, $userId, $role);
            $this->execute($insert);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO audit_assignments
                (period_id, auditee_id, lead_auditor, assignment_number, audit_type,
                 audit_date, status, notes, document_file, document_original_name, document_size,
                 assignment_date, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iiisssssssi",
            $data['period_id'],
            $data['auditee_id'],
            $data['lead_auditor'],
            $data['assignment_number'],
            $data['audit_type'],
            $data['audit_date'],
            $data['status'],
            $data['notes'],
            $data['document_file'],
            $data['document_original_name'],
            $data['document_size']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE audit_assignments
            SET
                period_id = ?, auditee_id = ?, lead_auditor = ?,
                assignment_number = ?, audit_type = ?, audit_date = ?,
                status = ?, notes = ?,
                document_file = ?, document_original_name = ?, document_size = ?,
                updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iiisssssssii",
            $data['period_id'],
            $data['auditee_id'],
            $data['lead_auditor'],
            $data['assignment_number'],
            $data['audit_type'],
            $data['audit_date'],
            $data['status'],
            $data['notes'],
            $data['document_file'],
            $data['document_original_name'],
            $data['document_size'],
            $id
        );

        $this->execute($stmt);

        return true;
    }

    public function getStandardIds(int $assignmentId): array
    {
        $stmt = $this->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");

        $stmt->bind_param("i", $assignmentId);

        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        return array_map(fn($r) => (int)$r['standard_id'], $rows);
    }

    public function saveStandards(int $assignmentId, array $standardIds): void
    {
        $delete = $this->prepare("DELETE FROM assignment_standards WHERE assignment_id = ?");
        $delete->bind_param("i", $assignmentId);
        $this->execute($delete);

        if (empty($standardIds)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO assignment_standards (assignment_id, standard_id) VALUES (?, ?)");

        foreach ($standardIds as $sid) {
            $sid = (int) $sid;
            $insert->bind_param("ii", $assignmentId, $sid);
            $this->execute($insert);
        }
    }

 /*
    |--------------------------------------------------------------------------
    |Delete
    |--------------------------------------------------------------------------
    */
    public function delete(int $id): bool
    {
        $stmt = $this->prepare("
            DELETE FROM audit_assignments
            WHERE id = ? AND status = 'Draft'
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return $stmt->affected_rows > 0;
    }
}