<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RtlReportService extends BaseService
{
    public function __construct(RtlReportRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RtlReportRepository
    {
        /** @var RtlReportRepository */
        return parent::repository();
    }

    public function getReportData(int $unitId, int $periodId): array
    {
        $unit = $this->repository()->getUnitInfo($unitId);
        $period = $this->repository()->getPeriodInfo($periodId);

        if (!$unit || !$period) {
            return $this->error('Unit atau Periode tidak ditemukan.');
        }

        return $this->success([
            'unit'      => $unit,
            'period'    => $period,
            'evidences' => $this->repository()->getEvidenceList($unitId, $periodId),
        ]);
    }
}