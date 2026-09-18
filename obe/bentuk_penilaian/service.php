<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class BentukPenilaianService extends BaseService
{
    public function __construct(BentukPenilaianRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): BentukPenilaianRepository
    {
        /** @var BentukPenilaianRepository */
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
            'nama_bentuk' => trim($data['nama_bentuk'] ?? ''),
            'deskripsi'   => trim($data['deskripsi'] ?? ''),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['nama_bentuk'] === '') {
            throw new InvalidArgumentException('Nama Bentuk Penilaian wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Bentuk Penilaian berhasil ditambahkan.');

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

            return $this->success(null, 'Bentuk Penilaian berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Bentuk Penilaian berhasil dihapus.');
    }
}