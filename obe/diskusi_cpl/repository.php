<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class DiskusiCplRepository extends BaseRepository
{
    protected string $table = 'obe_diskusi_cpl';

    public function getByMahasiswa(int $mahasiswaId): array
    {
        $stmt = $this->prepare("
            SELECT id, mahasiswa_id, penulis_nama, pesan, created_at
            FROM obe_diskusi_cpl
            WHERE mahasiswa_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->bind_param("i", $mahasiswaId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function create(int $mahasiswaId, string $penulisNama, string $pesan): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_diskusi_cpl (mahasiswa_id, penulis_nama, pesan, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iss", $mahasiswaId, $penulisNama, $pesan);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_diskusi_cpl WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}