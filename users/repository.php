<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    private function baseSelect(): string
    {
        return "
            SELECT
                us.id, us.role_id, us.full_name, us.username, us.email, us.phone,
                us.unit_id, us.status, us.created_at,
                r.role_name,
                u.name AS unit_name
            FROM users us
            LEFT JOIN roles r ON r.id = us.role_id
            LEFT JOIN units u ON u.id = us.unit_id
        ";
    }

    public function getAll(string $search = '', int $roleId = 0, int $status = -1, int $limit = 10, int $offset = 0): array
    {
        $sql = $this->baseSelect() . " WHERE 1 = 1 ";

        $types = '';
        $params = [];

        if ($roleId > 0) {
            $sql .= " AND us.role_id = ? ";
            $types .= "i";
            $params[] = $roleId;
        }

        if ($status !== -1) {
            $sql .= " AND us.status = ? ";
            $types .= "i";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (us.full_name LIKE ? OR us.username LIKE ? OR us.email LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "sss";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY us.full_name ASC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function count(string $search = '', int $roleId = 0, int $status = -1): int
    {
        $sql = "SELECT COUNT(*) AS total FROM users us WHERE 1 = 1";

        $types = '';
        $params = [];

        if ($roleId > 0) {
            $sql .= " AND us.role_id = ? ";
            $types .= "i";
            $params[] = $roleId;
        }

        if ($status !== -1) {
            $sql .= " AND us.status = ? ";
            $types .= "i";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (us.full_name LIKE ? OR us.username LIKE ? OR us.email LIKE ?) ";
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

    public function getStatistics(): array
    {
        return [
            'total'   => $this->count(),
            'aktif'   => $this->count('', 0, 1),
            'nonaktif'=> $this->count('', 0, 0),
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare($this->baseSelect() . " WHERE us.id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function existsUsername(string $username, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM users WHERE username = ?";

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

        return $this->fetchOne($stmt) !== null;
    }

    public function create(array $data): int
    {
        $sql = "
            INSERT INTO users
                (role_id, unit_id, full_name, username, password, email, phone, status, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iisssssi",
            $data['role_id'],
            $data['unit_id'],
            $data['full_name'],
            $data['username'],
            $data['password'],
            $data['email'],
            $data['phone'],
            $data['status']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE users
            SET role_id = ?, unit_id = ?, full_name = ?, username = ?, email = ?, phone = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "iissssii",
            $data['role_id'],
            $data['unit_id'],
            $data['full_name'],
            $data['username'],
            $data['email'],
            $data['phone'],
            $data['status'],
            $id
        );

        $this->execute($stmt);

        return true;
    }

    public function updatePassword(int $id, string $hashedPassword): bool
    {
        $stmt = $this->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $id);
        $this->execute($stmt);

        return true;
    }

    public function toggleStatus(int $id, int $status): bool
    {
        $stmt = $this->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $status, $id);
        $this->execute($stmt);

        return true;
    }
}