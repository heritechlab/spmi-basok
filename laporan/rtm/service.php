<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RtmReportService extends BaseService
{
    public function __construct(RtmReportRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RtmReportRepository
    {
        /** @var RtmReportRepository */
        return parent::repository();
    }

public function getReportData(int $unitId, int $periodId): array
    {
        $unit = $this->repository()->getUnitInfo($unitId);
        $period = $this->repository()->getPeriodInfo($periodId);

        if (!$unit || !$period) {
            return $this->error('Unit atau Periode tidak ditemukan.');
        }

        $meetings = $this->repository()->getMeetings($unitId, $periodId);

        if (empty($meetings)) {
            return $this->error('Belum ada Rapat RTM untuk Unit dan Periode ini.');
        }

        foreach ($meetings as &$m) {
            $m['documents'] = $this->repository()->getDocuments($m['id']);
            $m['action_plans'] = $this->repository()->getActionPlans($m['id']);
        }

        $ketuaLpm = $this->repository()->getKetuaLpm();

        $detailedPlans = $this->repository()->getDetailedActionPlans($unitId, $periodId);

        $priorityRank = [
            'Important-Urgent'       => 1,
            'Important-Not Urgent'   => 2,
            'Not Important-Urgent'   => 3,
            'Not Important-Not Urgent' => 4,
        ];

        $prioritizedPlans = $detailedPlans;

        usort($prioritizedPlans, function ($a, $b) use ($priorityRank) {
            $keyA = $a['importance'] . '-' . $a['urgency'];
            $keyB = $b['importance'] . '-' . $b['urgency'];

            $rankA = $priorityRank[$keyA] ?? 99;
            $rankB = $priorityRank[$keyB] ?? 99;

            return $rankA <=> $rankB;
        });

        return $this->success([
            'unit'              => $unit,
            'period'            => $period,
            'ketua_lpm'         => $ketuaLpm['full_name'] ?? '(...........................)',
            'notulis'           => $unit['head_name'] ?? '(...........................)',
            'meetings'          => $meetings,
            'detailed_plans'    => $detailedPlans,
            'prioritized_plans' => $prioritizedPlans,
            'all_documents'     => $this->repository()->getAllDocuments($unitId, $periodId),
        ]);
    }
}