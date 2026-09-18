<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class ProfilLulusanRepository extends BaseRepository
{
    protected string $table = 'obe_profil_lulusan';

    public function getAll(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_profil_lulusan
            WHERE unit_id = ? AND is_active = 1
            ORDER BY sort_order ASC, code ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_profil_lulusan WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function existsCode(string $code, int $unitId, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM obe_profil_lulusan WHERE code = ? AND unit_id = ? AND is_active = 1";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("sii", $code, $unitId, $excludeId);
        } else {
            $stmt->bind_param("si", $code, $unitId);
        }

        $this->execute($stmt);

        return (bool) $this->fetchOne($stmt);
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_profil_lulusan (unit_id, code, name, description, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssi", $data['unit_id'], $data['code'], $data['name'], $data['description'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_profil_lulusan
            SET code = ?, name = ?, description = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sssii", $data['code'], $data['name'], $data['description'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("UPDATE obe_profil_lulusan SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}