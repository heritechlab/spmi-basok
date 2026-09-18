<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class MahasiswaService extends BaseService
{
    public function __construct(MahasiswaRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): MahasiswaRepository
    {
        /** @var MahasiswaRepository */
        return parent::repository();
    }

    public function getAllByKurikulum(int $kurikulumId): array
    {
        return $this->success($this->repository()->getAllByKurikulum($kurikulumId));
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'unit_id'      => (int) ($data['unit_id'] ?? 0),
            'kurikulum_id' => (int) ($data['kurikulum_id'] ?? 0),
            'nim'          => trim($data['nim'] ?? ''),
            'nama'         => trim($data['nama'] ?? ''),
            'angkatan'     => (int) ($data['angkatan'] ?? 0),
            'status'       => trim($data['status'] ?? 'Aktif'),
            'pa_dosen_id'  => (int) ($data['pa_dosen_id'] ?? 0),
        ];
    }

    private function validate(array $data, int $excludeId = 0): void
    {
        $allowedStatus = ['Aktif', 'Cuti', 'Lulus', 'DO', 'Non-Aktif'];

        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Program Studi wajib dipilih.');
        }

        if ($data['kurikulum_id'] <= 0) {
            throw new InvalidArgumentException('Kurikulum wajib dipilih.');
        }

        if ($data['nim'] === '') {
            throw new InvalidArgumentException('NIM wajib diisi.');
        }

        if ($data['nama'] === '') {
            throw new InvalidArgumentException('Nama wajib diisi.');
        }

        if (!in_array($data['status'], $allowedStatus, true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }

        $existing = $this->repository()->findByNim($data['nim']);

        if ($existing && (int) $existing['id'] !== $excludeId) {
            throw new InvalidArgumentException('NIM ' . $data['nim'] . ' sudah terdaftar atas nama ' . $existing['nama'] . '.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Mahasiswa berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data, $id);

            $this->repository()->update($id, $data);

            return $this->success(null, 'Mahasiswa berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Mahasiswa berhasil dihapus.');
    }

    public function importBatch(array $rows, int $unitId, int $kurikulumId): array
    {
        $success = 0;
        $failed = [];

        foreach ($rows as $i => $row) {

            try {

                $data = $this->normalize([
                    'unit_id'      => $unitId,
                    'kurikulum_id' => $kurikulumId,
                    'nim'          => $row['nim'] ?? '',
                    'nama'         => $row['nama'] ?? '',
                    'angkatan'     => $row['angkatan'] ?? 0,
                    'status'       => $row['status'] ?? 'Aktif',
                ]);

                $this->validate($data);

                $this->repository()->create($data);
                $success++;

            } catch (Throwable $e) {
                $failed[] = 'Baris ' . ($i + 2) . ' (NIM: ' . ($row['nim'] ?? '-') . '): ' . $e->getMessage();
            }
        }

        return $this->success(['imported' => $success, 'failed' => $failed], "Berhasil impor {$success} data.");
    }
}