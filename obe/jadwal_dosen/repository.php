<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class JadwalDosenRepository extends BaseRepository
{
    protected string $table = 'obe_jadwal_dosen';

    public function getByMataKuliah(int $mkId, int $periodeId): array
    {
        $stmt = $this->prepare("SELECT * FROM obe_jadwal_dosen WHERE mata_kuliah_id = ? AND periode_id = ? ORDER BY pertemuan ASC");
        $stmt->bind_param("ii", $mkId, $periodeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function upsert(int $mkId, int $periodeId, int $pertemuan, string $hari, string $jamMulai, string $jamSelesai, ?string $ruang, ?string $dosenPengampu): void
    {
        $stmt = $this->prepare("
            INSERT INTO obe_jadwal_dosen (mata_kuliah_id, periode_id, pertemuan, hari, jam_mulai, jam_selesai, ruang, dosen_pengampu)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                hari = VALUES(hari),
                jam_mulai = VALUES(jam_mulai),
                jam_selesai = VALUES(jam_selesai),
                ruang = VALUES(ruang),
                dosen_pengampu = VALUES(dosen_pengampu)
        ");
        $stmt->bind_param("iiisssss", $mkId, $periodeId, $pertemuan, $hari, $jamMulai, $jamSelesai, $ruang, $dosenPengampu);
        $this->execute($stmt);
    }

    public function deleteByMkPertemuan(int $mkId, int $periodeId, int $pertemuan): void
    {
        $stmt = $this->prepare("DELETE FROM obe_jadwal_dosen WHERE mata_kuliah_id = ? AND periode_id = ? AND pertemuan = ?");
        $stmt->bind_param("iii", $mkId, $periodeId, $pertemuan);
        $this->execute($stmt);
    }
}