<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class MahasiswaRepository extends BaseRepository
{
    protected string $table = 'obe_mahasiswa';

    public function getAllByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_mahasiswa
            WHERE kurikulum_id = ? AND is_active = 1
            ORDER BY nama ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_mahasiswa WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function findByNim(string $nim): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_mahasiswa WHERE nim = ? LIMIT 1");
        $stmt->bind_param("s", $nim);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $paDosenId = $data['pa_dosen_id'] > 0 ? $data['pa_dosen_id'] : null;

        $stmt = $this->prepare("
            INSERT INTO obe_mahasiswa (unit_id, kurikulum_id, nim, nama, angkatan, status, pa_dosen_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iissisi", $data['unit_id'], $data['kurikulum_id'], $data['nim'], $data['nama'], $data['angkatan'], $data['status'], $paDosenId);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $paDosenId = $data['pa_dosen_id'] > 0 ? $data['pa_dosen_id'] : null;

        $stmt = $this->prepare("
            UPDATE obe_mahasiswa
            SET kurikulum_id = ?, nim = ?, nama = ?, angkatan = ?, status = ?, pa_dosen_id = ?
            WHERE id = ?
        ");
        $stmt->bind_param("issisii", $data['kurikulum_id'], $data['nim'], $data['nama'], $data['angkatan'], $data['status'], $paDosenId, $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("UPDATE obe_mahasiswa SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}