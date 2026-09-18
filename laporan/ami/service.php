<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AmiReportService extends BaseService
{
    public function __construct(AmiReportRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AmiReportRepository
    {
        /** @var AmiReportRepository */
        return parent::repository();
    }

    public function getReportData(int $unitId, int $periodId): array
    {
        $unit = $this->repository()->getUnitInfo($unitId);
        $period = $this->repository()->getPeriodInfo($periodId);

        if (!$unit || !$period) {
            return $this->error('Unit atau Periode tidak ditemukan.');
        }

        $assignments = $this->repository()->getAssignments($unitId, $periodId);

        if (empty($assignments)) {
            return $this->error('Belum ada Penugasan Audit untuk Unit dan Periode ini.');
        }

        /*
        |--------------------------------------------------------------------------
        | Gabungkan Ketua Tim (lead_auditor per penugasan) + Anggota Tim
        |--------------------------------------------------------------------------
        */

        $teamMap = [];

        foreach ($assignments as $a) {
            if (!empty($a['lead_auditor_name'])) {
                $teamMap[$a['lead_auditor_name']] = 'Ketua Tim Audit';
            }
        }

        $members = $this->repository()->getTeamMembers($unitId, $periodId);

        foreach ($members as $m) {
            if (!isset($teamMap[$m['full_name']])) {
                $teamMap[$m['full_name']] = 'Anggota Tim Audit';
            }
        }

        $team = [];
        foreach ($teamMap as $name => $role) {
            $team[] = ['full_name' => $name, 'role' => $role];
        }

        /*
        |--------------------------------------------------------------------------
        | Ketua Tim Audit utama (untuk Halaman Pengesahan — ambil dari penugasan pertama)
        |--------------------------------------------------------------------------
        */

        $ketuaTimUtama = $assignments[0]['lead_auditor_name'] ?? '-';

        return $this->success([
            'unit'                => $unit,
            'period'              => $period,
            'ketua_lpm'           => $this->repository()->getKetuaLpm(),
            'ketua_tim_utama'     => $ketuaTimUtama,
            'assignments'         => $assignments,
            'audited_standards'   => $this->repository()->getAuditedStandards($unitId, $periodId),
            'team'                => $team,
            'findings'            => $this->repository()->getFindings($unitId, $periodId),
            'statistics'          => $this->repository()->getFindingStatistics($unitId, $periodId),
            'action_plans'        => $this->repository()->getActionPlans($unitId, $periodId),
            'standard_summary'    => $this->repository()->getStandardAchievementSummary($unitId, $periodId),
        ]);
    }
}