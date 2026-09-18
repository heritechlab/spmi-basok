<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AmiInstitutionReportService extends BaseService
{
    public function __construct(AmiInstitutionReportRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AmiInstitutionReportRepository
    {
        /** @var AmiInstitutionReportRepository */
        return parent::repository();
    }

    public function getReportData(int $periodId): array
    {
        $period = $this->repository()->getPeriodInfo($periodId);

        if (!$period) {
            return $this->error('Periode tidak ditemukan.');
        }

        $assignments = $this->repository()->getAssignments($periodId);

        if (empty($assignments)) {
            return $this->error('Belum ada Penugasan Audit untuk Periode ini.');
        }

        $ketuaLpm = $this->repository()->getKetuaLpm();

        return $this->success([
            'period'             => $period,
            'ketua_lpm'          => $ketuaLpm['full_name'] ?? '(...........................)',
            'assignments'        => $assignments,
            'audited_units'      => $this->repository()->getAuditedUnits($periodId),
            'audited_standards'  => $this->repository()->getAuditedStandards($periodId),
            'team'               => $this->repository()->getTeamMembers($periodId),
            'findings'           => $this->repository()->getFindings($periodId),
            'statistics'         => $this->repository()->getFindingStatistics($periodId),
            'action_plans'       => $this->repository()->getActionPlans($periodId),
            'unit_summary'       => $this->repository()->getUnitAchievementSummary($periodId),
            'standard_summary'   => $this->repository()->getStandardAchievementSummary($periodId),
        ]);
    }
}