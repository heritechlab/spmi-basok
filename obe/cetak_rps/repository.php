<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class CetakRpsRepository extends BaseRepository
{
    protected string $table = 'obe_mata_kuliah';

    public function getInstitutionProfile(): ?array
    {
        $stmt = $this->prepare("SELECT * FROM institution_profile ORDER BY id ASC LIMIT 1");
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getProfilProdi(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT * FROM obe_profil_prodi WHERE unit_id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getMataKuliah(int $mkId): ?array
    {
        $stmt = $this->prepare("
            SELECT mk.*, u.head_name AS ka_prodi_name, u.name AS unit_name
            FROM obe_mata_kuliah mk
            LEFT JOIN units u ON u.id = mk.unit_id
            WHERE mk.id = ? LIMIT 1
        ");
        $stmt->bind_param("i", $mkId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getDosenList(int $mkId, int $periodeId): array
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

    public function getDosenById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT name, gelar_depan, gelar_belakang FROM obe_dosen WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getCplList(int $mkId): array
    {
        $stmt = $this->prepare("
            SELECT cl.id, cl.code, cl.description
            FROM obe_cpl_mk_map m
            JOIN obe_cpl cl ON cl.id = m.cpl_id
            WHERE m.mata_kuliah_id = ?
            ORDER BY cl.sort_order ASC
        ");
        $stmt->bind_param("i", $mkId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getCpmkList(int $mkId): array
    {
        $stmt = $this->prepare("
            SELECT c.*, cl.code AS cpl_code
            FROM obe_cpmk c
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            WHERE c.mata_kuliah_id = ?
            ORDER BY c.sort_order ASC
        ");
        $stmt->bind_param("i", $mkId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getSubCpmkList(int $mkId): array
    {
        $stmt = $this->prepare("
            SELECT sc.*, c.code AS cpmk_code
            FROM obe_sub_cpmk sc
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            WHERE c.mata_kuliah_id = ?
            ORDER BY c.sort_order ASC, sc.sort_order ASC
        ");
        $stmt->bind_param("i", $mkId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function getRpsList(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT r.*, sc.id AS sub_cpmk_id, sc.code AS sub_cpmk_code, sc.description AS sub_cpmk_description,
                   c.id AS cpmk_id, c.code AS cpmk_code, c.cpl_id, cl.code AS cpl_code
            FROM obe_rps r
            JOIN obe_sub_cpmk sc ON sc.id = r.sub_cpmk_id
            JOIN obe_cpmk c ON c.id = sc.cpmk_id
            LEFT JOIN obe_cpl cl ON cl.id = c.cpl_id
            WHERE r.mata_kuliah_id = ? AND r.periode_id = ?
            ORDER BY r.pertemuan ASC, r.sort_order ASC
        ");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['bentuk_luring']         = $this->getBentukLuring((int) $row['id']);
            $row['metode_luring']         = $this->getMetodeLuring((int) $row['id']);
            $row['metode_daring']         = $this->getMetodeDaring((int) $row['id']);
            $row['bahan_kajian']          = $this->getBahanKajian((int) $row['id']);
            $row['rencana_evaluasi_list'] = $this->getMappedRencanaEvaluasi((int) $row['id']);
        }

        return $rows;
    }

    public function getBentukLuring(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT bp.id, bp.nama_bentuk, bp.kategori
            FROM obe_rps_bentuk_luring_map m
            JOIN obe_master_bentuk_pembelajaran bp ON bp.id = m.bentuk_pembelajaran_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMetodeLuring(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT mp.id, mp.nama_metode, mp.aktivitas_mahasiswa
            FROM obe_rps_metode_luring_map m
            JOIN obe_master_metode_pembelajaran mp ON mp.id = m.metode_pembelajaran_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMetodeDaring(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT mp.id, mp.nama_metode, mp.aktivitas_mahasiswa
            FROM obe_rps_metode_daring_map m
            JOIN obe_master_metode_pembelajaran mp ON mp.id = m.metode_pembelajaran_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getBahanKajian(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT bk.id, bk.nama_bahan_kajian
            FROM obe_rps_bahan_kajian_map m
            JOIN obe_bahan_kajian bk ON bk.id = m.bahan_kajian_id
            WHERE m.rps_id = ?
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMappedRencanaEvaluasi(int $rpsId): array
    {
        $stmt = $this->prepare("
            SELECT re.id, re.basis_evaluasi, re.komponen_siakad, re.bobot_persen
            FROM obe_rps_rencana_evaluasi_map m
            JOIN obe_rencana_evaluasi re ON re.id = m.rencana_evaluasi_id
            WHERE m.rps_id = ?
            ORDER BY re.id ASC
        ");
        $stmt->bind_param("i", $rpsId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {
            $row['indikator'] = $this->getMappedIndikatorForRencanaEvaluasi((int) $row['id']);
        }

        return $rows;
    }

    public function getMappedIndikatorForRencanaEvaluasi(int $reoId): array
    {
        $stmt = $this->prepare("
            SELECT ip.indikator, r.nama_kriteria, r.deskripsi AS kriteria_deskripsi
            FROM obe_rencana_evaluasi_indikator_map m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            LEFT JOIN obe_rubrik_kriteria r ON r.indikator_penilaian_id = ip.id
            WHERE m.rencana_evaluasi_id = ?
        ");
        $stmt->bind_param("i", $reoId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function getJadwalDosen(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT pertemuan, hari, jam_mulai, jam_selesai, ruang, dosen_pengampu
            FROM obe_jadwal_dosen
            WHERE mata_kuliah_id = ? AND periode_id = ?
            ORDER BY pertemuan ASC
        ");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getRubrikIndikator(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("
            SELECT ip.id, ip.teknik_penilaian, ip.taksonomi_ranah, ip.indikator
            FROM obe_rubrik_penilaian_mk m
            JOIN obe_indikator_penilaian ip ON ip.id = m.indikator_penilaian_id
            WHERE m.mata_kuliah_id = ? AND m.periode_id = ?
            ORDER BY ip.taksonomi_ranah ASC, ip.sort_order ASC
        ");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getMahasiswaListByKurikulum(int $kurikulumId): array
    {
        $stmt = $this->prepare("
            SELECT id, nim, nama
            FROM obe_mahasiswa
            WHERE kurikulum_id = ? AND is_active = 1 AND status = 'Aktif'
            ORDER BY nama ASC
        ");
        $stmt->bind_param("i", $kurikulumId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}