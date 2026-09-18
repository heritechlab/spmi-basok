<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class PeriodeAkademikService extends BaseService
{
    public function __construct(PeriodeAkademikRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): PeriodeAkademikRepository
    {
        /** @var PeriodeAkademikRepository */
        return parent::repository();
    }

    public function getAll(): array
    {
        return $this->success($this->repository()->getAll());
    }

    public function getActive(): array
    {
        $data = $this->repository()->getActive();

        if (!$data) {
            return $this->error('Belum ada Periode Akademik yang aktif.');
        }

        return $this->success($data);
    }

    public function create(array $input): array
    {
        $tahunAjaran = trim($input['tahun_ajaran'] ?? '');
        $jenisSemester = trim($input['jenis_semester'] ?? '');
        $tanggalMulai = trim($input['tanggal_mulai'] ?? '') ?: null;
        $tanggalSelesai = trim($input['tanggal_selesai'] ?? '') ?: null;

        if ($tahunAjaran === '') {
            return $this->error('Tahun Ajaran wajib diisi (contoh: 2026/2027).');
        }

        if (!in_array($jenisSemester, ['Ganjil', 'Genap'], true)) {
            return $this->error('Jenis Semester wajib dipilih.');
        }

        try {
            $id = $this->repository()->create([
                'tahun_ajaran'    => $tahunAjaran,
                'jenis_semester'  => $jenisSemester,
                'tanggal_mulai'   => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
            ]);

            return $this->success(['id' => $id], 'Periode Akademik berhasil ditambahkan.');
        } catch (Throwable $e) {
            return $this->error('Gagal menyimpan (mungkin Periode ini sudah ada): ' . $e->getMessage());
        }
    }

    public function setActive(int $id): array
    {
        $this->repository()->setActive($id);

        return $this->success(null, 'Periode Akademik aktif berhasil diperbarui.');
    }

    public function delete(int $id): array
    {
        $ok = $this->repository()->delete($id);

        if (!$ok) {
            return $this->error('Tidak bisa menghapus Periode yang sedang aktif, atau data tidak ditemukan.');
        }

        return $this->success(null, 'Periode Akademik dihapus.');
    }
}