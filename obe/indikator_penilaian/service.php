<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class IndikatorPenilaianService extends BaseService
{
    public function __construct(IndikatorPenilaianRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): IndikatorPenilaianRepository
    {
        /** @var IndikatorPenilaianRepository */
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
            'teknik_penilaian'  => trim($data['teknik_penilaian'] ?? ''),
            'taksonomi_ranah'   => trim($data['taksonomi_ranah'] ?? 'Kognitif'),
            'taksonomi_jenjang' => trim($data['taksonomi_jenjang'] ?? 'Sarjana'),
            'indikator'         => trim($data['indikator'] ?? ''),
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        $allowedRanah = ['Kognitif', 'Afektif', 'Psikomotorik'];
        $allowedJenjang = ['Sarjana', 'Profesi'];

        if ($data['teknik_penilaian'] === '') {
            throw new InvalidArgumentException('Bentuk Penilaian wajib dipilih.');
        }

        if (!in_array($data['taksonomi_ranah'], $allowedRanah, true)) {
            throw new InvalidArgumentException('Ranah Taksonomi tidak valid.');
        }

        if (!in_array($data['taksonomi_jenjang'], $allowedJenjang, true)) {
            throw new InvalidArgumentException('Jenjang tidak valid.');
        }

        if ($data['indikator'] === '') {
            throw new InvalidArgumentException('Indikator wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Indikator Penilaian berhasil ditambahkan.');

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

            return $this->success(null, 'Indikator Penilaian berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Indikator Penilaian berhasil dihapus.');
    }

    private function normalizeRubrik(array $data): array
    {
        return [
            'indikator_penilaian_id' => (int) ($data['indikator_penilaian_id'] ?? 0),
            'nama_kriteria'          => trim($data['nama_kriteria'] ?? ''),
            'deskripsi'              => trim($data['deskripsi'] ?? ''),
            'sort_order'             => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validateRubrik(array $data): void
    {
        if ($data['indikator_penilaian_id'] <= 0) {
            throw new InvalidArgumentException('Indikator Penilaian tidak valid.');
        }

        if ($data['nama_kriteria'] === '') {
            throw new InvalidArgumentException('Nama Kriteria wajib diisi.');
        }
    }

    public function createRubrik(array $input): array
    {
        try {

            $data = $this->normalizeRubrik($input);
            $this->validateRubrik($data);

            $id = $this->repository()->createRubrik($data);

            return $this->success(['id' => $id], 'Kriteria Rubrik berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateRubrik(int $id, array $input): array
    {
        try {

            $data = $this->normalizeRubrik($input);
            $this->validateRubrik($data);

            $this->repository()->updateRubrik($id, $data);

            return $this->success(null, 'Kriteria Rubrik berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteRubrik(int $id): array
    {
        $this->repository()->deleteRubrik($id);

        return $this->success(null, 'Kriteria Rubrik berhasil dihapus.');
    }

    public function getRubrikById(int $id): array
    {
        $data = $this->repository()->findRubrikById($id);

        if (!$data) {
            return $this->error('Data tidak ditemukan.');
        }

        return $this->success($data);
    }
}