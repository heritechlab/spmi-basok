<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class PeriodService extends BaseService
{
    public function __construct(PeriodRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): PeriodRepository
    {
        /** @var PeriodRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', string $status = '', int $limit = 10, int $offset = 0): array
    {
        return $this->success(
            $this->repository()->getAll($search, $status, $limit, $offset)
        );
    }

    public function count(string $search = '', string $status = ''): array
    {
        return $this->success(
            $this->repository()->count($search, $status)
        );
    }

    public function getStatistics(): array
    {
        return $this->success(
            $this->repository()->getStatistics()
        );
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data periode tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'period_name'   => trim($data['period_name'] ?? ''),
            'year'          => trim($data['year'] ?? ''),
            'academic_year' => trim($data['academic_year'] ?? ''),
            'start_date'    => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'      => !empty($data['end_date']) ? $data['end_date'] : null,
            'status'        => trim($data['status'] ?? 'Draft'),
            'description'   => trim($data['description'] ?? ''),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['period_name'] === '') {
            throw new InvalidArgumentException('Nama periode wajib diisi.');
        }

        if ($data['year'] === '') {
            throw new InvalidArgumentException('Tahun wajib diisi.');
        }

        if ($data['academic_year'] === '') {
            throw new InvalidArgumentException('Tahun akademik wajib diisi.');
        }

        if (!in_array($data['status'], ['Draft', 'Aktif', 'Ditutup'], true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }

        if (
            !empty($data['start_date']) &&
            !empty($data['end_date']) &&
            $data['start_date'] > $data['end_date']
        ) {
            throw new InvalidArgumentException('Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Periode audit berhasil ditambahkan.');

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

            return $this->success(null, 'Periode audit berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        if ($id <= 0) {
            return $this->error('ID periode tidak valid.');
        }

        $this->repository()->delete($id);

        return $this->success([], 'Periode audit berhasil ditutup.');
    }
}