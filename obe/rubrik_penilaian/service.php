<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RubrikPenilaianService extends BaseService
{
    public function __construct(RubrikPenilaianRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RubrikPenilaianRepository
    {
        /** @var RubrikPenilaianRepository */
        return parent::repository();
    }

    public function getMaster(): array
    {
        $rows = $this->repository()->getAllIndikatorWithKriteria();

        foreach ($rows as &$row) {
            $row['kriteria_list'] = $row['kriteria_list'] ? explode('|||', $row['kriteria_list']) : [];
        }

        return $this->success($rows);
    }

    public function getByMataKuliah(int $mkId, int $periodeId): array
    {
        return $this->success($this->repository()->getMappedByMataKuliah($mkId, $periodeId));
    }

    public function save(int $mkId, int $periodeId, array $rows): array
    {
        try {
            $this->repository()->replaceForMataKuliah($mkId, $periodeId, $rows);

            return $this->success(null, 'Rubrik Penilaian berhasil disimpan.');
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}