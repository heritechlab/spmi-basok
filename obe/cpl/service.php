<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class CplService extends BaseService
{
    public function __construct(CplRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): CplRepository
    {
        /** @var CplRepository */
        return parent::repository();
    }

    public function getAll(int $unitId): array
    {
        return $this->success($this->repository()->getAll($unitId));
    }

        public function getMatriks(int $unitId): array
    {
        return $this->success($this->repository()->getMatriksData($unitId));
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
            'unit_id'             => (int) ($data['unit_id'] ?? 0),
            'code'                => trim($data['code'] ?? ''),
            'aspek_list'          => $data['aspek_list'] ?? [],
            'description'         => trim($data['description'] ?? ''),
            'sort_order'          => (int) ($data['sort_order'] ?? 0),
            'profil_lulusan_ids'  => $data['profil_lulusan_ids'] ?? [],
            'bobot_map'           => $data['bobot_map'] ?? [],
        ];
    }

    private function validate(array $data): void
    {
        $allowedAspek = ['Sikap', 'Pengetahuan', 'Keterampilan Umum', 'Keterampilan Khusus'];

        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Program Studi wajib dipilih.');
        }

        if ($data['code'] === '') {
            throw new InvalidArgumentException('Kode CPL wajib diisi.');
        }

        foreach ($data['aspek_list'] as $a) {
            if (!in_array($a, $allowedAspek, true)) {
                throw new InvalidArgumentException('Aspek CPL tidak valid.');
            }
        }

        if ($data['description'] === '') {
            throw new InvalidArgumentException('Deskripsi CPL wajib diisi.');
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

            return $this->success(['id' => $id], 'CPL berhasil ditambahkan.');

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

            return $this->success(null, 'CPL berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'CPL berhasil dihapus.');
    }
}