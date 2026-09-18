<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class PeriodeAkademikRepository extends BaseRepository
{
    protected string $table = 'obe_periode_akademik';

    public function getAll(): array
    {
        $stmt = $this->prepare("SELECT * FROM obe_periode_akademik ORDER BY tahun_ajaran DESC, jenis_semester ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getActive(): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_periode_akademik WHERE is_active = 1 LIMIT 1");
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_periode_akademik (tahun_ajaran, jenis_semester, tanggal_mulai, tanggal_selesai)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $data['tahun_ajaran'], $data['jenis_semester'], $data['tanggal_mulai'], $data['tanggal_selesai']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function setActive(int $id): void
    {
        $reset = $this->prepare("UPDATE obe_periode_akademik SET is_active = 0");
        $this->execute($reset);

        $set = $this->prepare("UPDATE obe_periode_akademik SET is_active = 1 WHERE id = ?");
        $set->bind_param("i", $id);
        $this->execute($set);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_periode_akademik WHERE id = ? AND is_active = 0");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $stmt->affected_rows > 0;
    }
}