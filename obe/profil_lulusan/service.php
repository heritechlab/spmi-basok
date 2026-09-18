<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class ProfilLulusanService extends BaseService
{
    public function __construct(ProfilLulusanRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): ProfilLulusanRepository
    {
        /** @var ProfilLulusanRepository */
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
            'unit_id'     => (int) ($data['unit_id'] ?? 0),
            'code'        => trim($data['code'] ?? ''),
            'name'        => trim($data['name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Program Studi wajib dipilih.');
        }

        if ($data['code'] === '') {
            throw new InvalidArgumentException('Kode Profil Lulusan wajib diisi.');
        }

        if ($data['name'] === '') {
            throw new InvalidArgumentException('Nama Profil Lulusan wajib diisi.');
        }

        if ($data['description'] === '') {
            throw new InvalidArgumentException('Deskripsi Profil Lulusan wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            if ($this->repository()->existsCode($data['code'], $data['unit_id'])) {
                throw new InvalidArgumentException('Kode sudah digunakan pada Program Studi ini.');
            }

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Profil Lulusan berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            if ($this->repository()->existsCode($data['code'], $data['unit_id'], $id)) {
                throw new InvalidArgumentException('Kode sudah digunakan pada Program Studi ini.');
            }

            $this->repository()->update($id, $data);

            return $this->success(null, 'Profil Lulusan berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Profil Lulusan berhasil dihapus.');
    }
}