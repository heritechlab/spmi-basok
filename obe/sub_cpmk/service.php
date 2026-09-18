<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class SubCpmkService extends BaseService
{
    public function __construct(SubCpmkRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): SubCpmkRepository
    {
        /** @var SubCpmkRepository */
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
            'cpmk_id'     => (int) ($data['cpmk_id'] ?? 0),
            'code'        => trim($data['code'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'bobot'       => (float) ($data['bobot'] ?? 0),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['cpmk_id'] <= 0) {
            throw new InvalidArgumentException('CPMK wajib dipilih.');
        }

        if ($data['code'] === '') {
            throw new InvalidArgumentException('Kode Sub-CPMK wajib diisi.');
        }

        if ($data['description'] === '') {
            throw new InvalidArgumentException('Deskripsi Sub-CPMK wajib diisi.');
        }

        if ($data['bobot'] < 0 || $data['bobot'] > 100) {
            throw new InvalidArgumentException('Bobot harus di antara 0 - 100.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobotByCpmk($data['cpmk_id']);

            if (($totalExisting + $data['bobot']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot Sub-CPMK untuk CPMK ini akan melebihi 100% (saat ini sudah ' . $totalExisting . '%).');
            }

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Sub-CPMK berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobotByCpmk($data['cpmk_id'], $id);

            if (($totalExisting + $data['bobot']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot Sub-CPMK untuk CPMK ini akan melebihi 100% (Sub-CPMK lain sudah ' . $totalExisting . '%).');
            }

            $this->repository()->update($id, $data);

            return $this->success(null, 'Sub-CPMK berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Sub-CPMK berhasil dihapus.');
    }
}