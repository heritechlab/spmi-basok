<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class MetodePembelajaranRepository extends BaseRepository
{
    protected string $table = 'obe_master_metode_pembelajaran';

    public function getAll(): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_master_metode_pembelajaran
            WHERE is_active = 1
            ORDER BY kategori ASC, sort_order ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_master_metode_pembelajaran WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_master_metode_pembelajaran (kategori, nama_metode, aktivitas_mahasiswa, aktivitas_dosen, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssssi", $data['kategori'], $data['nama_metode'], $data['aktivitas_mahasiswa'], $data['aktivitas_dosen'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_master_metode_pembelajaran
            SET kategori = ?, nama_metode = ?, aktivitas_mahasiswa = ?, aktivitas_dosen = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssii", $data['kategori'], $data['nama_metode'], $data['aktivitas_mahasiswa'], $data['aktivitas_dosen'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("UPDATE obe_master_metode_pembelajaran SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}