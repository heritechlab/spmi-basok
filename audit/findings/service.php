<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class FindingService extends BaseService
{
    public function __construct(FindingRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): FindingRepository
    {
        /** @var FindingRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', int $periodId = 0, int $unitId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($search, $periodId, $unitId, $status, $limit, $offset));
    }

    public function count(string $search = '', int $periodId = 0, int $unitId = 0, string $status = ''): array
    {
        return $this->success($this->repository()->count($search, $periodId, $unitId, $status));
    }

    public function getStatistics(int $unitId = 0): array
    {
        return $this->success($this->repository()->getStatistics($unitId));
    }
    public function getDetail(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Temuan tidak ditemukan.');
        }

        return $this->success($data);
    }
}