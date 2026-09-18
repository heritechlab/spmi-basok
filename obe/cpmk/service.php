<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class CpmkService extends BaseService
{
    public function __construct(CpmkRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): CpmkRepository
    {
        /** @var CpmkRepository */
        return parent::repository();
    }

    public function getAllByMataKuliah(int $mataKuliahId): array
    {
        return $this->success($this->repository()->getAllByMataKuliah($mataKuliahId));
    }

        public function getAllByKurikulum(int $kurikulumId): array
    {
        return $this->success($this->repository()->getAllByKurikulum($kurikulumId));
    }
        public function getMatriksCpmkCpl(int $unitId, int $kurikulumId): array
    {
        return $this->success($this->repository()->getMatriksCpmkCpl($unitId, $kurikulumId));
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
            'mata_kuliah_id' => (int) ($data['mata_kuliah_id'] ?? 0),
            'cpl_id'         => !empty($data['cpl_id']) ? (int) $data['cpl_id'] : null,
            'code'           => trim($data['code'] ?? ''),
            'description'    => trim($data['description'] ?? ''),
            'bobot'          => (float) ($data['bobot'] ?? 0),
            'sort_order'     => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['mata_kuliah_id'] <= 0) {
            throw new InvalidArgumentException('Mata Kuliah wajib dipilih.');
        }

        if (!$data['cpl_id']) {
            throw new InvalidArgumentException('CPL wajib dipilih.');
        }

        if ($data['code'] === '') {
            throw new InvalidArgumentException('Kode CPMK wajib diisi.');
        }

        if ($data['description'] === '') {
            throw new InvalidArgumentException('Deskripsi CPMK wajib diisi.');
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

            $totalExisting = $this->repository()->getTotalBobotByMataKuliah($data['mata_kuliah_id']);

            if (($totalExisting + $data['bobot']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot CPMK untuk Mata Kuliah ini akan melebihi 100% (saat ini sudah ' . $totalExisting . '%).');
            }

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'CPMK berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobotByMataKuliah($data['mata_kuliah_id'], $id);

            if (($totalExisting + $data['bobot']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot CPMK untuk Mata Kuliah ini akan melebihi 100% (CPMK lain sudah ' . $totalExisting . '%).');
            }

            $this->repository()->update($id, $data);

            return $this->success(null, 'CPMK berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'CPMK berhasil dihapus.');
    }
}