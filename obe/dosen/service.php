<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class DosenService extends BaseService
{
    public function __construct(DosenRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): DosenRepository
    {
        /** @var DosenRepository */
        return parent::repository();
    }

    public function getAll(int $unitId): array
    {
        return $this->success($this->repository()->getAll($unitId));
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
            'unit_id'        => (int) ($data['unit_id'] ?? 0),
            'nidn'           => trim($data['nidn'] ?? '') ?: null,
            'name'           => trim($data['name'] ?? ''),
            'gelar_depan'    => trim($data['gelar_depan'] ?? '') ?: null,
            'gelar_belakang' => trim($data['gelar_belakang'] ?? '') ?: null,
        ];
    }

    private function validate(array $data): void
    {
        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Program Studi wajib dipilih.');
        }

        if ($data['name'] === '') {
            throw new InvalidArgumentException('Nama Dosen wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Dosen berhasil ditambahkan.');

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

            return $this->success(null, 'Dosen berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Dosen berhasil dihapus.');
    }
}