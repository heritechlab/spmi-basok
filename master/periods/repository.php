<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class PeriodRepository extends BaseRepository
{
    protected string $table = 'audit_periods';

    /*
    |--------------------------------------------------------------------------
    | Get All
    |--------------------------------------------------------------------------
    */

    public function getAll(
        string $search = '',
        string $status = '',
        int $limit = 10,
        int $offset = 0
    ): array {

        $sql = "
            SELECT id, period_name, year, academic_year, start_date, end_date, status, description
            FROM audit_periods
            WHERE 1 = 1
        ";

        $types  = '';
        $params = [];

        if ($status !== '') {
            $sql .= " AND status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (period_name LIKE ? OR year LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "ss";
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY year DESC, academic_year ASC LIMIT ? OFFSET ? ";

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

    public function count(string $search = '', string $status = ''): int
    {

        $sql = "SELECT COUNT(*) AS total FROM audit_periods WHERE 1 = 1";

        $types  = '';
        $params = [];

        if ($status !== '') {
            $sql .= " AND status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (period_name LIKE ? OR year LIKE ?) ";
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
            'aktif'   => $this->count('', 'Aktif'),
            'draft'   => $this->count('', 'Draft'),
            'ditutup' => $this->count('', 'Ditutup'),
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
            SELECT id, period_name, year, academic_year, start_date, end_date, status, description
            FROM audit_periods
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Deactivate Other Active Periods
    |--------------------------------------------------------------------------
    */

    public function closeOtherActivePeriods(int $excludeId = 0): void
    {
        $stmt = $this->prepare("
            UPDATE audit_periods
            SET status = 'Ditutup', updated_at = NOW()
            WHERE status = 'Aktif' AND id <> ?
        ");

        $stmt->bind_param("i", $excludeId);

        $this->execute($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO audit_periods
                (period_name, year, academic_year, start_date, end_date, status, description, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "sssssss",
            $data['period_name'],
            $data['year'],
            $data['academic_year'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $data['description']
        );

        $this->execute($stmt);

        $newId = $this->insertId();

        if ($data['status'] === 'Aktif') {
            $this->closeOtherActivePeriods($newId);
        }

        return $newId;
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE audit_periods
            SET
                period_name = ?, year = ?, academic_year = ?,
                start_date = ?, end_date = ?, status = ?, description = ?,
                updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "sssssssi",
            $data['period_name'],
            $data['year'],
            $data['academic_year'],
            $data['start_date'],
            $data['end_date'],
            $data['status'],
            $data['description'],
            $id
        );

        $this->execute($stmt);

        if ($data['status'] === 'Aktif') {
            $this->closeOtherActivePeriods($id);
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete (Soft Delete -> Ditutup)
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("
            UPDATE audit_periods
            SET status = 'Ditutup', updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return true;
    }
}