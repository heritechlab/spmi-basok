<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class GenerateAkunRepository extends BaseRepository
{
    protected string $table = 'users';

    public function getDosenTanpaAkun(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT d.id, d.nidn, d.name, d.gelar_depan, d.gelar_belakang
            FROM obe_dosen d
            LEFT JOIN users u ON u.dosen_id = d.id
            WHERE d.unit_id = ? AND d.is_active = 1 AND u.id IS NULL AND d.nidn IS NOT NULL AND TRIM(d.nidn) != ''
            ORDER BY d.name ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMahasiswaTanpaAkun(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT m.id, m.nim, m.nama
            FROM obe_mahasiswa m
            LEFT JOIN users u ON u.mahasiswa_id = m.id
            WHERE m.kurikulum_id = ? AND m.is_active = 1 AND m.status = 'Aktif' AND u.id IS NULL
            ORDER BY m.nama ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createUserDosen(int $dosenId, int $unitId, string $username, string $fullName, string $passwordHash): int
    {
        $stmt = $this->prepare("
            INSERT INTO users (role_id, unit_id, dosen_id, full_name, username, password, must_change_password, status, created_at)
            VALUES (6, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        $stmt->bind_param("iisss", $unitId, $dosenId, $fullName, $username, $passwordHash);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function createUserMahasiswa(int $mahasiswaId, int $unitId, string $username, string $fullName, string $passwordHash): int
    {
        $stmt = $this->prepare("
            INSERT INTO users (role_id, unit_id, mahasiswa_id, full_name, username, password, must_change_password, status, created_at)
            VALUES (7, ?, ?, ?, ?, ?, 1, 1, NOW())
        ");
        $stmt->bind_param("iisss", $unitId, $mahasiswaId, $fullName, $username, $passwordHash);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $this->execute($stmt);

        return $this->fetchOne($stmt) !== null;
    }
}