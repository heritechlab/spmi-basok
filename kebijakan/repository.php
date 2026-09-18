<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class KebijakanRepository extends BaseRepository
{
    protected string $table = 'quality_policies';

    public function getByCategory(string $category): array
    {
        $stmt = $this->prepare("SELECT * FROM quality_policies WHERE category = ? ORDER BY tanggal_berlaku DESC, created_at DESC");
        $stmt->bind_param("s", $category);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM quality_policies WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO quality_policies
                (category, title, description, nomor_dokumen, tanggal_berlaku, file_path, file_original_name, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssi",
            $data['category'], $data['title'], $data['description'], $data['nomor_dokumen'],
            $data['tanggal_berlaku'], $data['file_path'], $data['file_original_name'], $data['uploaded_by']
        );
        $this->execute($stmt);

        return $this->insertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->prepare("DELETE FROM quality_policies WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }
}