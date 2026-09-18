<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class BentukPenilaianRepository extends BaseRepository
{
    protected string $table = 'obe_master_bentuk_penilaian';

    public function getAll(): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_master_bentuk_penilaian
            ORDER BY sort_order ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_master_bentuk_penilaian WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_master_bentuk_penilaian (nama_bentuk, deskripsi, sort_order, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssi", $data['nama_bentuk'], $data['deskripsi'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_master_bentuk_penilaian
            SET nama_bentuk = ?, deskripsi = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $data['nama_bentuk'], $data['deskripsi'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_master_bentuk_penilaian WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}