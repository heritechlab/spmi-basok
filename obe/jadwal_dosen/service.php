<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class JadwalDosenService extends BaseService
{
    public function __construct(JadwalDosenRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): JadwalDosenRepository
    {
        /** @var JadwalDosenRepository */
        return parent::repository();
    }

    public function getByMataKuliah(int $mkId, int $periodeId): array
    {
        return $this->success($this->repository()->getByMataKuliah($mkId, $periodeId));
    }

    public function saveBulk(int $mkId, int $periodeId, array $rows): array
    {
        $allowedHari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        try {
            foreach ($rows as $row) {
                $pertemuan = (int) ($row['pertemuan'] ?? 0);
                $hari = trim($row['hari'] ?? '');
                $jamMulai = trim($row['jam_mulai'] ?? '');
                $jamSelesai = trim($row['jam_selesai'] ?? '');
                $ruang = trim($row['ruang'] ?? '') ?: null;
                $dosenPengampu = trim($row['dosen_pengampu'] ?? '') ?: null;

                if ($pertemuan <= 0) {
                    continue;
                }

                // Baris kosong (belum diisi Hari/Jam) dilewati - tidak wajib semua Pertemuan diisi sekaligus
                if ($hari === '' || $jamMulai === '' || $jamSelesai === '') {
                    continue;
                }

                if (!in_array($hari, $allowedHari, true)) {
                    continue;
                }

                $this->repository()->upsert(
                    $mkId,
                    $periodeId,
                    $pertemuan,
                    $hari,
                    $jamMulai . ':00',
                    $jamSelesai . ':00',
                    $ruang,
                    $dosenPengampu
                );
            }

            return $this->success(null, 'Jadwal Dosen berhasil disimpan.');
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteRow(int $mkId, int $periodeId, int $pertemuan): array
    {
        $this->repository()->deleteByMkPertemuan($mkId, $periodeId, $pertemuan);

        return $this->success(null, 'Jadwal dihapus.');
    }
}