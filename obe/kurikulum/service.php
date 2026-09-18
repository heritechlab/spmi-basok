<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class KurikulumService extends BaseService
{
    public function __construct(KurikulumRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): KurikulumRepository
    {
        /** @var KurikulumRepository */
        return parent::repository();
    }

    public function getAll(int $unitId): array
    {
        $list = $this->repository()->getAll($unitId);

        foreach ($list as &$row) {
            $row['jumlah_mk'] = $this->repository()->countMataKuliah((int) $row['id']);
        }

        return $this->success($list);
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
            'unit_id'   => (int) ($data['unit_id'] ?? 0),
            'tahun'     => (int) ($data['tahun'] ?? 0),
            'nama'      => trim($data['nama'] ?? ''),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];
    }

    private function validate(array $data): void
    {
        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Program Studi wajib dipilih.');
        }

        if ($data['tahun'] < 2000 || $data['tahun'] > 2100) {
            throw new InvalidArgumentException('Tahun tidak valid.');
        }

        if ($data['nama'] === '') {
            throw new InvalidArgumentException('Nama Kurikulum wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Kurikulum berhasil ditambahkan.');

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

            return $this->success(null, 'Kurikulum berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}