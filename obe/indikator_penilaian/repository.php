<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class IndikatorPenilaianRepository extends BaseRepository
{
    protected string $table = 'obe_indikator_penilaian';

    public function getAll(): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_indikator_penilaian
            ORDER BY sort_order ASC
        ");
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['rubrik'] = $this->getRubrik((int) $row['id']);
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_indikator_penilaian WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_indikator_penilaian (teknik_penilaian, taksonomi_ranah, taksonomi_jenjang, indikator, sort_order, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("ssssi", $data['teknik_penilaian'], $data['taksonomi_ranah'], $data['taksonomi_jenjang'], $data['indikator'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_indikator_penilaian
            SET teknik_penilaian = ?, taksonomi_ranah = ?, taksonomi_jenjang = ?, indikator = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssii", $data['teknik_penilaian'], $data['taksonomi_ranah'], $data['taksonomi_jenjang'], $data['indikator'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_indikator_penilaian WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function getRubrik(int $indikatorId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_rubrik_kriteria
            WHERE indikator_penilaian_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("i", $indikatorId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createRubrik(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_rubrik_kriteria (indikator_penilaian_id, nama_kriteria, deskripsi, sort_order, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issi", $data['indikator_penilaian_id'], $data['nama_kriteria'], $data['deskripsi'], $data['sort_order']);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function updateRubrik(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_rubrik_kriteria
            SET nama_kriteria = ?, deskripsi = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $data['nama_kriteria'], $data['deskripsi'], $data['sort_order'], $id);
        $this->execute($stmt);

        return true;
    }

    public function deleteRubrik(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM obe_rubrik_kriteria WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }

    public function findRubrikById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_rubrik_kriteria WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }
}