<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class AuditorRepository extends BaseRepository
{
    protected string $table = 'users';

    public const ROLE_AUDITOR = 3;

    /*
    |--------------------------------------------------------------------------
    | Get All
    |--------------------------------------------------------------------------
    */

 public function getAll(
        string $search = '',
        int $status = 1,
        int $limit = 10,
        int $offset = 0
    ): array {

        $sql = "
            SELECT
                id, full_name, nidn_nip, username, email, phone, status,
                certificate_number, certificate_file, certificate_original_name
            FROM users
            WHERE role_id = " . self::ROLE_AUDITOR . "
        ";

        $types  = '';
        $params = [];

        if ($status >= 0) {
            $sql .= " AND status = ? ";
            $types .= "i";
            $params[] = $status;
        }

        if ($search !== '') {

            $sql .= "
                AND (
                    full_name LIKE ?
                    OR nidn_nip LIKE ?
                    OR username LIKE ?
                    OR email LIKE ?
                )
            ";

            $keyword = "%{$search}%";

            $types .= "ssss";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY full_name ASC LIMIT ? OFFSET ? ";

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

public function count(string $search = '', int $status = 1): int
    {

        $sql = "
            SELECT COUNT(*) AS total
            FROM users
            WHERE role_id = " . self::ROLE_AUDITOR . "
        ";

        $types  = '';
        $params = [];

        if ($status >= 0) {
            $sql .= " AND status = ? ";
            $types .= "i";
            $params[] = $status;
        }

        if ($search !== '') {

            $sql .= "
                AND (
                    full_name LIKE ?
                    OR nidn_nip LIKE ?
                    OR username LIKE ?
                    OR email LIKE ?
                )
            ";

            $keyword = "%{$search}%";

            $types .= "ssss";
            $params[] = $keyword;
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
    | count card
    |--------------------------------------------------------------------------
    */

    public function countAll(): int
    {
        return $this->count('', -1);
    }

    public function countByStatus(int $status): int
    {
        return $this->count('', $status);
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
                id, full_name, nidn_nip, username, email, phone, status,
                certificate_number, certificate_file, certificate_original_name, certificate_size
            FROM users
            WHERE id = ? AND role_id = " . self::ROLE_AUDITOR . "
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Exists Username
    |--------------------------------------------------------------------------
    */

    public function existsUsername(string $username, int $excludeId = 0): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM users WHERE username = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("si", $username, $excludeId);
        } else {
            $stmt->bind_param("s", $username);
        }

        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        return ((int)($row['total'] ?? 0)) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO users
                (role_id, full_name, nidn_nip, username, password, email, phone, status,
                 certificate_number, certificate_file, certificate_original_name, certificate_size, created_at)
            VALUES
                (" . self::ROLE_AUDITOR . ", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "ssssssisssi",
            $data['full_name'],
            $data['nidn_nip'],
            $data['username'],
            $data['password'],
            $data['email'],
            $data['phone'],
            $data['status'],
            $data['certificate_number'],
            $data['certificate_file'],
            $data['certificate_original_name'],
            $data['certificate_size']
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
        if (!empty($data['password'])) {

            $sql = "
                UPDATE users
                SET
                    full_name = ?, nidn_nip = ?, username = ?, password = ?,
                    email = ?, phone = ?, status = ?,
                    certificate_number = ?, certificate_file = ?,
                    certificate_original_name = ?, certificate_size = ?,
                    updated_at = NOW()
                WHERE id = ? AND role_id = " . self::ROLE_AUDITOR . "
            ";

            $stmt = $this->prepare($sql);

            $stmt->bind_param(
                "ssssssisssii",
                $data['full_name'],
                $data['nidn_nip'],
                $data['username'],
                $data['password'],
                $data['email'],
                $data['phone'],
                $data['status'],
                $data['certificate_number'],
                $data['certificate_file'],
                $data['certificate_original_name'],
                $data['certificate_size'],
                $id
            );

        } else {

            $sql = "
                UPDATE users
                SET
                    full_name = ?, nidn_nip = ?, username = ?,
                    email = ?, phone = ?, status = ?,
                    certificate_number = ?, certificate_file = ?,
                    certificate_original_name = ?, certificate_size = ?,
                    updated_at = NOW()
                WHERE id = ? AND role_id = " . self::ROLE_AUDITOR . "
            ";

            $stmt = $this->prepare($sql);

            $stmt->bind_param(
                "sssssisssii",
                $data['full_name'],
                $data['nidn_nip'],
                $data['username'],
                $data['email'],
                $data['phone'],
                $data['status'],
                $data['certificate_number'],
                $data['certificate_file'],
                $data['certificate_original_name'],
                $data['certificate_size'],
                $id
            );
        }

        $this->execute($stmt);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete (Soft Delete)
    |--------------------------------------------------------------------------
    */

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("
            UPDATE users
            SET status = 0, updated_at = NOW()
            WHERE id = ? AND role_id = " . self::ROLE_AUDITOR . "
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return true;
    }
}