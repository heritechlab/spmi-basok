<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class CapaianKriteriaService extends BaseService
{
    public function __construct(CapaianKriteriaRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): CapaianKriteriaRepository
    {
        /** @var CapaianKriteriaRepository */
        return parent::repository();
    }

    public function getProdiCriteria(): array
    {
        return $this->success($this->repository()->getProdiCriteria());
    }

    public function getInstitutionCriteria(): array
    {
        return $this->success($this->repository()->getInstitutionCriteria());
    }

    public function getStandardsFlat(): array
    {
        return $this->success($this->repository()->getStandardsFlat());
    }

    public function saveStandardMapping(int $standardId, int $prodiCriteriaId, int $institutionCriteriaId): array
    {
        try {

            $this->repository()->saveStandardMapping(
                $standardId,
                $prodiCriteriaId > 0 ? $prodiCriteriaId : null,
                $institutionCriteriaId > 0 ? $institutionCriteriaId : null
            );

            return $this->success(null, 'Pemetaan Kriteria berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getPeriods(): array
    {
        return $this->success($this->repository()->getPeriods());
    }

    public function getProdiUnits(): array
    {
        return $this->success($this->repository()->getProdiUnits());
    }

    public function getProdiCriteriaScores(int $unitId, int $periodId): array
    {
        $criteriaList = $this->repository()->getProdiCriteria();
        $standards = $this->repository()->getStandardsFlat();
        $achievements = $this->repository()->getStandardAchievementByUnitWithFallback($unitId, $periodId);

        $skorByStandardId = [];
        foreach ($achievements as $a) {
            $skorByStandardId[$a['standard_id']] = (float) $a['skor_capaian'];
        }

        $result = [];

        foreach ($criteriaList as $crit) {

            $mappedStandards = array_filter($standards, fn($s) => (int) ($s['prodi_criteria_id'] ?? 0) === (int) $crit['id']);

            $scores = [];
            $detail = [];

            foreach ($mappedStandards as $s) {

                $skor = $skorByStandardId[$s['id']] ?? null;

                if ($skor !== null) {
                    $scores[] = $skor;
                }

                $detail[] = [
                    'standard_id'   => $s['id'],
                    'standard_code' => $s['code'],
                    'standard_name' => $s['name'],
                    'skor'          => $skor,
                ];
            }

            $avg = !empty($scores) ? round(array_sum($scores) / count($scores), 1) : null;

            $result[] = [
                'criteria_id'   => $crit['id'],
                'criteria_name' => $crit['name'],
                'skor'          => $avg,
                'standards'     => $detail,
            ];
        }

        return $this->success($result);
    }

     public function getInstitutionCriteriaScores(int $periodId): array
    {
        $criteriaListFlat = $this->repository()->getInstitutionCriteria();
        $standards = $this->repository()->getStandardsFlat();
        $achievements = $this->repository()->getStandardAchievementInstitution($periodId);

        $skorByStandardId = [];
        foreach ($achievements as $a) {
            $skorByStandardId[$a['standard_id']] = (float) $a['skor_capaian'];
        }

        // Tentukan mana yg berperan sebagai INDUK (punya anak) - induk itu dilewati,
        // tidak ditampilkan sebagai entri sendiri, karena anak-anaknya (2.1/2.2/2.3)
        // sudah ditampilkan mandiri.
        $parentIds = [];
        foreach ($criteriaListFlat as $c) {
            if ($c['parent_id'] !== null) {
                $parentIds[$c['parent_id']] = true;
            }
        }

        $result = [];

        foreach ($criteriaListFlat as $crit) {

            if (isset($parentIds[$crit['id']])) {
                continue;
            }

            $mappedStandards = array_filter($standards, fn($s) => (int) ($s['institution_criteria_id'] ?? 0) === (int) $crit['id']);

            $scores = [];
            $detail = [];

            foreach ($mappedStandards as $s) {

                $skor = $skorByStandardId[$s['id']] ?? null;

                if ($skor !== null) {
                    $scores[] = $skor;
                }

                $detail[] = [
                    'standard_id'   => $s['id'],
                    'standard_code' => $s['code'],
                    'standard_name' => $s['name'],
                    'skor'          => $skor,
                ];
            }

            $avg = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;

            $result[] = [
                'criteria_id'   => $crit['id'],
                'criteria_name' => $crit['name'],
                'skor'          => $avg,
                'standards'     => $detail,
            ];
        }

        return $this->success($result);
    }
}