<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class MataKuliahRepository extends BaseRepository
{
    protected string $table = 'obe_mata_kuliah';

    public function getAll(int $kurikulumId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT mk.*, u.head_name AS ka_prodi_name
            FROM obe_mata_kuliah mk
            LEFT JOIN units u ON u.id = mk.unit_id
            WHERE mk.kurikulum_id = ? AND mk.is_active = 1
            ORDER BY mk.semester ASC, mk.name ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['dosen_list'] = $this->getMappedDosen((int) $row['id'], $periodeId);
        }

        return $rows;
    }

    public function findById(int $id, int $periodeId): ?array
    {
        $stmt = $this->prepare("
            SELECT mk.*, u.head_name AS ka_prodi_name
            FROM obe_mata_kuliah mk
            LEFT JOIN units u ON u.id = mk.unit_id
            WHERE mk.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        if ($row) {
            $row['cpl_ids'] = array_column($this->getMappedCpl($id), 'id');
            $row['dosen_list'] = $this->getMappedDosen($id, $periodeId);
        }

        return $row ?: null;
    }

    public function getMappedCpl(int $mkId): array
    {
        $stmt = $this->prepare("
            SELECT c.id, c.code
            FROM obe_cpl_mk_map m
            JOIN obe_cpl c ON c.id = m.cpl_id
            WHERE m.mata_kuliah_id = ?
            ORDER BY c.sort_order ASC
        ");
        $stmt->bind_param("i", $mkId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMappedDosen(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT d.id, d.name, d.gelar_depan, d.gelar_belakang, m.peran
            FROM obe_mata_kuliah_dosen m
            JOIN obe_dosen d ON d.id = m.dosen_id
            WHERE m.mata_kuliah_id = ? AND m.periode_id = ?
            ORDER BY FIELD(m.peran, 'Koordinator', 'Anggota'), d.name ASC
        ");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveDosenMapping(int $mkId, int $periodeId, array $dosenList): void
    {
        $delete = $this->prepare("DELETE FROM obe_mata_kuliah_dosen WHERE mata_kuliah_id = ? AND periode_id = ?");
        $delete->bind_param("ii", $mkId, $periodeId);
        $this->execute($delete);

        if (empty($dosenList)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_mata_kuliah_dosen (mata_kuliah_id, periode_id, dosen_id, peran) VALUES (?, ?, ?, ?)");

        foreach ($dosenList as $item) {
            $dosenId = (int) ($item['dosen_id'] ?? 0);
            $peran = ($item['peran'] ?? 'Anggota') === 'Koordinator' ? 'Koordinator' : 'Anggota';

            if ($dosenId <= 0) {
                continue;
            }

            $insert->bind_param("iiis", $mkId, $periodeId, $dosenId, $peran);
            $this->execute($insert);
        }
    }

    public function saveCplMapping(int $mkId, array $cplIds): void
    {
        $delete = $this->prepare("DELETE FROM obe_cpl_mk_map WHERE mata_kuliah_id = ?");
        $delete->bind_param("i", $mkId);
        $this->execute($delete);

        if (empty($cplIds)) {
            return;
        }

        $insert = $this->prepare("INSERT INTO obe_cpl_mk_map (cpl_id, mata_kuliah_id) VALUES (?, ?)");

        foreach ($cplIds as $cplId) {
            $cplId = (int) $cplId;
            $insert->bind_param("ii", $cplId, $mkId);
            $this->execute($insert);
        }
    }

    public function getMatriksCplMk(int $unitId): array
    {
        $stmtCpl = $this->prepare("SELECT id, code FROM obe_cpl WHERE unit_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmtCpl->bind_param("i", $unitId);
        $this->execute($stmtCpl);
        $cplList = $this->fetchAll($stmtCpl);

        $stmtMk = $this->prepare("SELECT id, code, name, semester FROM obe_mata_kuliah WHERE unit_id = ? AND is_active = 1 ORDER BY semester ASC, name ASC");
        $stmtMk->bind_param("i", $unitId);
        $this->execute($stmtMk);
        $mkList = $this->fetchAll($stmtMk);

        $stmtMap = $this->prepare("
            SELECT m.cpl_id, m.mata_kuliah_id
            FROM obe_cpl_mk_map m
            JOIN obe_mata_kuliah mk ON mk.id = m.mata_kuliah_id
            WHERE mk.unit_id = ?
        ");
        $stmtMap->bind_param("i", $unitId);
        $this->execute($stmtMap);
        $mapRows = $this->fetchAll($stmtMap);

        $mapSet = [];
        foreach ($mapRows as $m) {
            $mapSet[$m['cpl_id'] . '_' . $m['mata_kuliah_id']] = true;
        }

        return [
            'cpl'          => $cplList,
            'mata_kuliah'  => $mkList,
            'map'          => $mapSet,
        ];
    }

    public function create(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO obe_mata_kuliah
                (unit_id, kurikulum_id, code, name, semester, jenis_mk, kelompok_mk, konsentrasi,
                 sks_tatap_muka, sks_praktikum, sks_praktek_lapangan, sks_simulasi,
                 minimal_nilai_lulus, rumpun_mk, dosen_pengembang_rps_id, ada_diktat, ada_silabus, validasi_rps,
                 tahun_ajaran, tanggal_revisi_rps, gkm_dosen_id, deskripsi, media_pembelajaran,
                 prasyarat_mk, pustaka_utama, pustaka_pendukung,
                 created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iississsiiiissiiisssisssss",
            $data['unit_id'], $data['kurikulum_id'], $data['code'], $data['name'], $data['semester'], $data['jenis_mk'],
            $data['kelompok_mk'], $data['konsentrasi'],
            $data['sks_tatap_muka'], $data['sks_praktikum'], $data['sks_praktek_lapangan'], $data['sks_simulasi'],
            $data['minimal_nilai_lulus'], $data['rumpun_mk'], $data['dosen_pengembang_rps_id'],
            $data['ada_diktat'], $data['ada_silabus'], $data['validasi_rps'],
            $data['tahun_ajaran'], $data['tanggal_revisi_rps'], $data['gkm_dosen_id'], $data['deskripsi'],
            $data['media_pembelajaran'], $data['prasyarat_mk'], $data['pustaka_utama'], $data['pustaka_pendukung']
        );
        $this->execute($stmt);

        $id = $this->insertId();

        $this->saveCplMapping($id, $data['cpl_ids'] ?? []);
        $this->saveDosenMapping($id, (int) ($data['periode_id'] ?? 0), $data['dosen_list'] ?? []);

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->prepare("
            UPDATE obe_mata_kuliah
            SET code = ?, name = ?, semester = ?, jenis_mk = ?, kelompok_mk = ?, konsentrasi = ?,
                sks_tatap_muka = ?, sks_praktikum = ?, sks_praktek_lapangan = ?, sks_simulasi = ?,
                minimal_nilai_lulus = ?, rumpun_mk = ?, dosen_pengembang_rps_id = ?,
                ada_diktat = ?, ada_silabus = ?, validasi_rps = ?,
                tahun_ajaran = ?, tanggal_revisi_rps = ?, gkm_dosen_id = ?, deskripsi = ?, media_pembelajaran = ?,
                prasyarat_mk = ?, pustaka_utama = ?, pustaka_pendukung = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssisssiiiissiiisssisssssi",
            $data['code'], $data['name'], $data['semester'], $data['jenis_mk'], $data['kelompok_mk'], $data['konsentrasi'],
            $data['sks_tatap_muka'], $data['sks_praktikum'], $data['sks_praktek_lapangan'], $data['sks_simulasi'],
            $data['minimal_nilai_lulus'], $data['rumpun_mk'], $data['dosen_pengembang_rps_id'],
            $data['ada_diktat'], $data['ada_silabus'], $data['validasi_rps'],
            $data['tahun_ajaran'], $data['tanggal_revisi_rps'], $data['gkm_dosen_id'], $data['deskripsi'],
            $data['media_pembelajaran'], $data['prasyarat_mk'], $data['pustaka_utama'], $data['pustaka_pendukung'],
            $id
        );
        $this->execute($stmt);

        $this->saveCplMapping($id, $data['cpl_ids'] ?? []);
        $this->saveDosenMapping($id, (int) ($data['periode_id'] ?? 0), $data['dosen_list'] ?? []);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("UPDATE obe_mata_kuliah SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
}