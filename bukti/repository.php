<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class BuktiRepository extends BaseRepository
{
    protected string $table = 'bukti_pelaksanaan';

public function getAll(
        string $category = '',
        int $unitId = 0,
        string $semester = '',
        string $academicYear = '',
        string $fileType = '',
        int $limit = 20,
        int $offset = 0
    ): array
    {
        $sql = "
            SELECT bp.*, us.full_name AS uploaded_by_name, u.name AS unit_name, u.code AS unit_code
            FROM bukti_pelaksanaan bp
            LEFT JOIN users us ON us.id = bp.uploaded_by
            LEFT JOIN units u ON u.id = bp.unit_id
            WHERE 1 = 1
        ";

        $types = '';
        $params = [];

        if ($category !== '') {
            $sql .= " AND bp.category = ? ";
            $types .= "s";
            $params[] = $category;
        }

        if ($unitId > 0) {
            $sql .= " AND bp.unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($semester !== '') {
            $sql .= " AND bp.semester = ? ";
            $types .= "s";
            $params[] = $semester;
        }

        if ($academicYear !== '') {
            $sql .= " AND bp.academic_year = ? ";
            $types .= "s";
            $params[] = $academicYear;
        }

        if ($fileType !== '') {
            $sql .= " AND bp.file_type = ? ";
            $types .= "s";
            $params[] = $fileType;
        }

        $sql .= " ORDER BY bp.created_at DESC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function count(
        string $category = '',
        int $unitId = 0,
        string $semester = '',
        string $academicYear = '',
        string $fileType = ''
    ): int
    {
        $sql = "SELECT COUNT(*) AS total FROM bukti_pelaksanaan WHERE 1 = 1";

        $types = '';
        $params = [];

        if ($category !== '') {
            $sql .= " AND category = ? ";
            $types .= "s";
            $params[] = $category;
        }

        if ($unitId > 0) {
            $sql .= " AND unit_id = ? ";
            $types .= "i";
            $params[] = $unitId;
        }

        if ($semester !== '') {
            $sql .= " AND semester = ? ";
            $types .= "s";
            $params[] = $semester;
        }

        if ($academicYear !== '') {
            $sql .= " AND academic_year = ? ";
            $types .= "s";
            $params[] = $academicYear;
        }

        if ($fileType !== '') {
            $sql .= " AND file_type = ? ";
            $types .= "s";
            $params[] = $fileType;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM bukti_pelaksanaan WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO bukti_pelaksanaan
                (category, unit_id, semester, academic_year, document_name, description, file_path, file_original_name, file_type, uploaded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "sisssssssi",
            $data['category'],
            $data['unit_id'],
            $data['semester'],
            $data['academic_year'],
            $data['document_name'],
            $data['description'],
            $data['file_path'],
            $data['file_original_name'],
            $data['file_type'],
            $data['uploaded_by']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM bukti_pelaksanaan WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}