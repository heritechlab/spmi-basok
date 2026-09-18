<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class PtpReportService extends BaseService
{
    public function __construct(PtpReportRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): PtpReportRepository
    {
        /** @var PtpReportRepository */
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
            return $this->error('Belum ada Rapat PTP untuk Unit dan Periode ini.');
        }

        foreach ($meetings as &$m) {
            $m['participants'] = $this->repository()->getParticipants($m['id']);
            $m['items'] = $this->repository()->getItems($m['id']);
        }

        $ketuaLpm = $this->repository()->getKetuaLpm();

        return $this->success([
            'unit'      => $unit,
            'period'    => $period,
            'ketua_lpm' => $ketuaLpm['full_name'] ?? '(...........................)',
            'notulis'   => $unit['head_name'] ?? '(...........................)',
            'meetings'  => $meetings,
        ]);
    }
}