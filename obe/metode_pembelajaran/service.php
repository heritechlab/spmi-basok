<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class MetodePembelajaranService extends BaseService
{
    public function __construct(MetodePembelajaranRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): MetodePembelajaranRepository
    {
        /** @var MetodePembelajaranRepository */
        return parent::repository();
    }

    public function getAll(): array
    {
        return $this->success($this->repository()->getAll());
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
            'kategori'             => trim($data['kategori'] ?? 'Reguler'),
            'nama_metode'          => trim($data['nama_metode'] ?? ''),
            'aktivitas_mahasiswa'  => trim($data['aktivitas_mahasiswa'] ?? ''),
            'aktivitas_dosen'      => trim($data['aktivitas_dosen'] ?? ''),
            'sort_order'           => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        $allowedKategori = ['Reguler', 'Program Studi Profesi', 'Daring'];

        if (!in_array($data['kategori'], $allowedKategori, true)) {
            throw new InvalidArgumentException('Kategori tidak valid.');
        }

        if ($data['nama_metode'] === '') {
            throw new InvalidArgumentException('Nama Metode Pembelajaran wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Metode Pembelajaran berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $this->repository()->update($id, $data);

            return $this->success(null, 'Metode Pembelajaran berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Metode Pembelajaran berhasil dihapus.');
    }
}