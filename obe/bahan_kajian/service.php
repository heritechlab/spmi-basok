<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class BahanKajianService extends BaseService
{
    public function __construct(BahanKajianRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): BahanKajianRepository
    {
        /** @var BahanKajianRepository */
        return parent::repository();
    }

    public function getAllByUnit(int $unitId): array
    {
        return $this->success($this->repository()->getAllByUnit($unitId));
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
            'sub_cpmk_id'        => (int) ($data['sub_cpmk_id'] ?? 0),
            'nama_bahan_kajian'  => trim($data['nama_bahan_kajian'] ?? ''),
            'deskripsi'          => trim($data['deskripsi'] ?? ''),
            'sort_order'         => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['sub_cpmk_id'] <= 0) {
            throw new InvalidArgumentException('Sub-CPMK wajib dipilih.');
        }

        if ($data['nama_bahan_kajian'] === '') {
            throw new InvalidArgumentException('Nama Bahan Kajian wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Bahan Kajian berhasil ditambahkan.');

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

            return $this->success(null, 'Bahan Kajian berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Bahan Kajian berhasil dihapus.');
    }

    public function getSubCpmkList(int $unitId): array
    {
        return $this->success($this->repository()->getSubCpmkList($unitId));
    }
}