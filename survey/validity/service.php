<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../repository.php';

class SurveyValidityService extends BaseService
{
    private SurveyRepository $surveyRepository;

    public function __construct(SurveyValidityRepository $repository, SurveyRepository $surveyRepository)
    {
        parent::__construct($repository);
        $this->surveyRepository = $surveyRepository;
    }

    protected function repository(): SurveyValidityRepository
    {
        /** @var SurveyValidityRepository */
        return parent::repository();
    }

    public function getTestWithItems(int $typeId): array
    {
        $questions = $this->surveyRepository->getFlatQuestionList($typeId);
        $test = $this->repository()->getTest($typeId);

        $itemsMap = [];

        if ($test) {
            $items = $this->repository()->getItems((int) $test['id']);
            foreach ($items as $it) {
                $itemsMap[$it['question_id']] = $it;
            }
        }

        $rows = [];

        foreach ($questions as $q) {

            $existing = $itemsMap[$q['id']] ?? null;

            $rows[] = [
                'question_id'   => $q['id'],
                'question_text' => $q['question_text'],
                'category_name' => $q['category_name'],
                'r_hitung'      => $existing['r_hitung'] ?? null,
                'is_valid'      => $existing['is_valid'] ?? null,
            ];
        }

        return $this->success([
            'test'  => $test,
            'items' => $rows,
        ]);
    }

    public function save(int $typeId, array $input, int $updatedBy): array
    {
        try {

            $data = [
                'n_responden'       => !empty($input['n_responden']) ? (int) $input['n_responden'] : null,
                'r_tabel'           => !empty($input['r_tabel']) ? (float) $input['r_tabel'] : null,
                'cronbach_alpha'    => !empty($input['cronbach_alpha']) ? (float) $input['cronbach_alpha'] : null,
                'reliability_label' => trim($input['reliability_label'] ?? '') ?: null,
                'catatan'           => trim($input['catatan'] ?? '') ?: null,
                'updated_by'        => $updatedBy,
            ];

            $testId = $this->repository()->upsertTest($typeId, $data);

            $items = $input['items'] ?? [];

            foreach ($items as $questionId => $row) {

                $rHitung = isset($row['r_hitung']) && $row['r_hitung'] !== '' ? (float) $row['r_hitung'] : null;
                $isValid = isset($row['is_valid']) && $row['is_valid'] !== '' ? (int) $row['is_valid'] : null;

                $this->repository()->saveItem($testId, (int) $questionId, $rHitung, $isValid);
            }

            return $this->success(null, 'Hasil Uji Validitas & Reliabilitas berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}