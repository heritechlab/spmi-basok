<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class CplRepository extends BaseRepository
{
    protected string $table = 'obe_cpl';

    public function getAll(int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT * FROM obe_cpl
            WHERE unit_id = ? AND is_active = 1
            ORDER BY sort_order ASC, code ASC
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['profil_lulusan'] = $this->getMappedProfilLulusan((int) $row['id']);
            $row['aspek_list'] = $this->getMappedAspek((int) $row['id']);
        }

        return $rows;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_cpl WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        if ($row) {
            $row['profil_lulusan_detail'] = $this->getMappedProfilLulusan($id);
            $row['profil_lulusan_ids'] = array_column($row['profil_lulusan_detail'], 'id');
            $row['aspek_list'] = $this->getMappedAspek($id);
        }

        return $row ?: null;
    }

    public function getMappedAspek(int $cplId): array
    {
        $stmt = $this->prepare("SELECT aspek FROM obe_cpl_aspek_map WHERE cpl_id = ?");
        $stmt->bind_param("i", $cplId);
        $this->execute($stmt);

        return array_column($this->fetchAll($stmt), 'aspek');
    }

    private function saveAspek(int $cplId, array $aspekList): void
    {
        $delete = $this->prepare("DELETE FROM obe_cpl_aspek_map WHERE cpl_id = ?");
        $delete->bind_param("i", $cplId);
        $this->execute($delete);

        if (empty($aspekList)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_cpl_aspek_map (cpl_id, aspek) VALUES (?, ?)");

        foreach ($aspekList as $aspek) {
            $insert->bind_param("is", $cplId, $aspek);
            $this->execute($insert);
        }
    }

    public function getMappedProfilLulusan(int $cplId): array
    {
        $stmt = $this->prepare("
            SELECT pl.id, pl.code, pl.name, m.bobot
            FROM obe_cpl_profil_map m
            JOIN obe_profil_lulusan pl ON pl.id = m.profil_lulusan_id
            WHERE m.cpl_id = ?
            ORDER BY pl.sort_order ASC
        ");
        $stmt->bind_param("i", $cplId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMatriksData(int $unitId): array
    {
        $stmtPl = $this->prepare("
            SELECT id, code, name FROM obe_profil_lulusan
            WHERE unit_id = ? AND is_active = 1
            ORDER BY sort_order ASC
        ");
        $stmtPl->bind_param("i", $unitId);
        $this->execute($stmtPl);
        $profilLulusanList = $this->fetchAll($stmtPl);

        $stmtCpl = $this->prepare("SELECT id, code FROM obe_cpl WHERE unit_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmtCpl->bind_param("i", $unitId);
        $this->execute($stmtCpl);
        $cplList = $this->fetchAll($stmtCpl);

        $stmtMap = $this->prepare("
            SELECT m.profil_lulusan_id, m.cpl_id, m.bobot
            FROM obe_cpl_profil_map m
            JOIN obe_cpl c ON c.id = m.cpl_id
            WHERE c.unit_id = ?
        ");
        $stmtMap->bind_param("i", $unitId);
        $this->execute($stmtMap);
        $mapRows = $this->fetchAll($stmtMap);

        $mapSet = [];
        foreach ($mapRows as $m) {
            $mapSet[$m['profil_lulusan_id'] . '_' . $m['cpl_id']] = (float) $m['bobot'];
        }

        return [
            'profil_lulusan' => $profilLulusanList,
            'cpl'            => $cplList,
            'map'            => $mapSet,
        ];
    }

    public function existsCode(string $code, int $unitId, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM obe_cpl WHERE code = ? AND unit_id = ? AND is_active = 1";

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
            INSERT INTO obe_cpl (unit_id, code, description, sort_order, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issi", $data['unit_id'], $data['code'], $data['description'], $data['sort_order']);
        $this->execute($stmt);

        $id = $this->insertId();

        $this->saveMapping($id, $data['profil_lulusan_ids'] ?? [], $data['bobot_map'] ?? []);
        $this->saveAspek($id, $data['aspek_list'] ?? []);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_cpl
            SET code = ?, description = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssii", $data['code'], $data['description'], $data['sort_order'], $id);
        $this->execute($stmt);

        $this->saveMapping($id, $data['profil_lulusan_ids'] ?? [], $data['bobot_map'] ?? []);
        $this->saveAspek($id, $data['aspek_list'] ?? []);

        return true;
    }

    private function saveMapping(int $cplId, array $profilLulusanIds, array $bobotMap = []): void
    {
        $delete = $this->prepare("DELETE FROM obe_cpl_profil_map WHERE cpl_id = ?");
        $delete->bind_param("i", $cplId);
        $this->execute($delete);

        if (empty($profilLulusanIds)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_cpl_profil_map (cpl_id, profil_lulusan_id, bobot) VALUES (?, ?, ?)");

        foreach ($profilLulusanIds as $plId) {
            $plId = (int) $plId;
            $bobot = (float) ($bobotMap[$plId] ?? 0);
            $insert->bind_param("iid", $cplId, $plId, $bobot);
            $this->execute($insert);
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("UPDATE obe_cpl SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}