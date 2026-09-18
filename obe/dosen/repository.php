<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class DosenRepository extends BaseRepository
{
    protected string $table = 'obe_dosen';

    public function getAll(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_dosen
            WHERE unit_id = ? AND is_active = 1
            ORDER BY name ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_dosen WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_dosen (unit_id, nidn, name, gelar_depan, gelar_belakang, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issss", $data['unit_id'], $data['nidn'], $data['name'], $data['gelar_depan'], $data['gelar_belakang']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_dosen
            SET nidn = ?, name = ?, gelar_depan = ?, gelar_belakang = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssi", $data['nidn'], $data['name'], $data['gelar_depan'], $data['gelar_belakang'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("UPDATE obe_dosen SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function getMappedDosen(int $mataKuliahId): array
    {
        $stmt = $this->prepare("
            SELECT d.id, d.name, d.gelar_depan, d.gelar_belakang, m.peran
            FROM obe_mata_kuliah_dosen m
            JOIN obe_dosen d ON d.id = m.dosen_id
            WHERE m.mata_kuliah_id = ?
            ORDER BY FIELD(m.peran, 'Koordinator', 'Anggota'), d.name ASC
        ");
        $stmt->bind_param("i", $mataKuliahId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveDosenMapping(int $mataKuliahId, array $dosenList): void
    {
        $delete = $this->prepare("DELETE FROM obe_mata_kuliah_dosen WHERE mata_kuliah_id = ?");
        $delete->bind_param("i", $mataKuliahId);
        $this->execute($delete);

        if (empty($dosenList)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_mata_kuliah_dosen (mata_kuliah_id, dosen_id, peran) VALUES (?, ?, ?)");

        foreach ($dosenList as $item) {
            $dosenId = (int) ($item['dosen_id'] ?? 0);
            $peran = ($item['peran'] ?? 'Anggota') === 'Koordinator' ? 'Koordinator' : 'Anggota';

            if ($dosenId <= 0) {
                continue;
            }

            $insert->bind_param("iis", $mataKuliahId, $dosenId, $peran);
            $this->execute($insert);
        }
    }
}