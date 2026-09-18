<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class AkreditasiInstitusiRepository extends BaseRepository
{
    protected string $table = 'akreditasi_dokumen';

    public function getAll(string $kriteria = '', string $academicYear = '', int $limit = 20, int $offset = 0): array
    {
        $sql = "
            SELECT ad.*, us.full_name AS uploaded_by_name
            FROM akreditasi_dokumen ad
            LEFT JOIN users us ON us.id = ad.uploaded_by
            WHERE ad.tingkat = 'Institusi'
        ";

        $types = '';
        $params = [];

        if ($kriteria !== '') {
            $sql .= " AND ad.kriteria = ? ";
            $types .= "s";
            $params[] = $kriteria;
        }

        if ($academicYear !== '') {
            $sql .= " AND ad.academic_year = ? ";
            $types .= "s";
            $params[] = $academicYear;
        }

        $sql .= " ORDER BY ad.created_at DESC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function count(string $kriteria = '', string $academicYear = ''): int
    {
        $sql = "SELECT COUNT(*) AS total FROM akreditasi_dokumen WHERE tingkat = 'Institusi'";

        $types = '';
        $params = [];

        if ($kriteria !== '') {
            $sql .= " AND kriteria = ? ";
            $types .= "s";
            $params[] = $kriteria;
        }

        if ($academicYear !== '') {
            $sql .= " AND academic_year = ? ";
            $types .= "s";
            $params[] = $academicYear;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO akreditasi_dokumen
                (tingkat, kriteria, document_name, unit_id, academic_year, file_path, file_original_name, link_url, uploaded_by, created_at)
            VALUES ('Institusi', ?, ?, NULL, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssssssi",
            $data['kriteria'],
            $data['document_name'],
            $data['academic_year'],
            $data['file_path'],
            $data['file_original_name'],
            $data['link_url'],
            $data['uploaded_by']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM akreditasi_dokumen WHERE id = ? AND tingkat = 'Institusi'");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}