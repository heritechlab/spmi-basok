<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class LedReportService extends BaseService
{
    public function __construct(LedReportRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): LedReportRepository
    {
        /** @var LedReportRepository */
        return parent::repository();
    }

    public function getReportData(int $unitId, int $periodId): array
    {
        $unit = $this->repository()->getUnitInfo($unitId);
        $period = $this->repository()->getPeriodInfo($periodId);

        $assignmentIds = $this->repository()->getAssignmentIdsForUnitPeriod($unitId, $periodId);

        $kriteriaList = $this->repository()->getKriteria();
        $allowedStandardIds = $this->repository()->getAssignedStandardIds($assignmentIds);

        $babII = [];
        $totalIndikator = 0;
        $totalTerisi = 0;

        foreach ($kriteriaList as $kriteria) {

            $critId = (int) $kriteria['id'];
            $standards = $this->repository()->getStandardsByKriteria($critId, $allowedStandardIds);

            $standardsBlock = [];

            foreach ($standards as $std) {

                $indicators = $this->repository()->getIndicatorsByStandard((int) $std['id'], $unitId);
                $indicatorIds = array_column($indicators, 'id');

                $entries = $this->repository()->getEntriesAcrossAssignments($indicatorIds, $assignmentIds);

                foreach ($indicators as &$ind) {
                    $entry = $entries[$ind['id']] ?? null;
                    $ind['entry'] = $entry;

                    $totalIndikator++;
                    if (!empty($entry['notes'])) {
                        $totalTerisi++;
                    }
                }
                unset($ind);

                $standardsBlock[] = [
                    'standard_code' => $std['code'],
                    'standard_name' => $std['name'],
                    'indicators'    => $indicators,
                ];
            }

            if (!empty($standardsBlock)) {
                $babII[] = [
                    'criteria_id'   => $critId,
                    'criteria_name' => $kriteria['name'],
                    'standards'     => $standardsBlock,
                ];
            }
        }

        $narratives = $this->repository()->getNarratives($unitId, $periodId);
        $kriteriaAnalysis = $this->repository()->getKriteriaAnalysis($unitId, $periodId);
        $signature = $this->repository()->getSignature($unitId, $periodId);

        return [
            'unit'               => $unit,
            'period'             => $period,
            'bab_ii'             => $babII,
            'narratives'         => $narratives,
            'kriteria_analysis'  => $kriteriaAnalysis,
            'signature'          => $signature,
            'total_indikator'    => $totalIndikator,
            'total_terisi'       => $totalTerisi,
            'persen_terisi'      => $totalIndikator > 0 ? round(($totalTerisi / $totalIndikator) * 100) : 0,
        ];
    }

    public function saveNarrative(int $unitId, int $periodId, string $section, string $content, int $updatedBy): array
    {
        $allowedSections = ['kata_pengantar', 'dasar_penyusunan', 'tim_penyusun', 'mekanisme_kerja', 'kesimpulan'];

        if (!in_array($section, $allowedSections, true)) {
            return $this->error('Bagian narasi tidak valid.');
        }

        $this->repository()->saveNarrative($unitId, $periodId, $section, $content, $updatedBy);

        return $this->success(null, 'Narasi berhasil disimpan.');
    }

    public function saveKriteriaAnalysis(int $unitId, int $periodId, int $criteriaId, string $kekuatan, string $kelemahan, int $updatedBy): array
    {
        if ($criteriaId <= 0) {
            return $this->error('Kriteria tidak valid.');
        }

        $this->repository()->saveKriteriaAnalysis($unitId, $periodId, $criteriaId, $kekuatan, $kelemahan, $updatedBy);

        return $this->success(null, 'Analisis Kriteria berhasil disimpan.');
    }
}