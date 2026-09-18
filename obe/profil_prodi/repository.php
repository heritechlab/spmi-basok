<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class ProfilProdiRepository extends BaseRepository
{
    protected string $table = 'obe_profil_prodi';

    public function getByUnit(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_profil_prodi WHERE unit_id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function upsert(int $unitId, ?string $visi, ?string $misi, ?string $unggulan): void
    {
        $stmt = $this->prepare("
            INSERT INTO obe_profil_prodi (unit_id, visi, misi, unggulan)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE visi = VALUES(visi), misi = VALUES(misi), unggulan = VALUES(unggulan)
        ");
        $stmt->bind_param("isss", $unitId, $visi, $misi, $unggulan);
        $this->execute($stmt);
    }
}