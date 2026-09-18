<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class SurveyService extends BaseService
{
    public function __construct(SurveyRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): SurveyRepository
    {
        /** @var SurveyRepository */
        return parent::repository();
    }

    public function getAllTypes(): array
    {
        return $this->success($this->repository()->getAllTypes());
    }

    public function getTypeBySlug(string $slug): array
    {
        $type = $this->repository()->getTypeBySlug($slug);

        if (!$type) {
            return $this->error('Jenis survey tidak ditemukan.');
        }

        return $this->success($type);
    }

    public function getForm(int $typeId): array
    {
        return $this->success($this->repository()->getCategoriesWithQuestions($typeId));
    }

private function getIdentityFieldKeys(string $slug): array
    {
        $map = [
            'pengguna'         => ['nama_instansi', 'nama_pengisi', 'email', 'no_hp', 'jumlah_lulusan', 'masa_kerja'],
            'mitra'            => ['nama_instansi', 'bidang_kerjasama', 'nama_pengisi', 'jabatan', 'lama_kerjasama'],
            'mitra_penelitian' => ['nama_instansi', 'bidang_kerjasama', 'nama_pengisi', 'jabatan', 'lama_kerjasama'],
            'mitra_pkm'        => ['nama_instansi', 'bidang_kerjasama', 'nama_pengisi', 'jabatan', 'lama_kerjasama'],
        ];

        return $map[$slug] ?? [];
    }

public function submit(int $typeId, array $input): array
    {
        try {

            $unitId = !empty($input['unit_id']) ? (int) $input['unit_id'] : null;
            $saran = trim($input['saran'] ?? '');
            $answers = $input['answers'] ?? [];
            $surveyYear = (int) ($input['survey_year'] ?? 0);

            if ($surveyYear <= 0) {
                $surveyYear = (int) date('Y');
            }

            if (empty($answers)) {
                throw new InvalidArgumentException('Mohon isi seluruh pertanyaan survey.');
            }

            $totalPertanyaan = 0;
            foreach ($this->repository()->getCategoriesWithQuestions($typeId) as $cat) {
                $totalPertanyaan += count($cat['questions'] ?? []);
            }
            if (count($answers) < $totalPertanyaan) {
                throw new InvalidArgumentException('Mohon jawab seluruh pertanyaan survey (' . count($answers) . ' dari ' . $totalPertanyaan . ' terjawab) sebelum mengirim.');
            }

        $identity = [];
        $slugForResponse = $this->repository()->getSlugById($typeId);

            if ($this->repository()->requiresIdentity($typeId)) {

                $fieldKeys = $this->getIdentityFieldKeys($slugForResponse);

                foreach ($fieldKeys as $key) {
                    $identity[$key] = trim($input[$key] ?? '');

                    if ($identity[$key] === '') {
                        throw new InvalidArgumentException('Mohon lengkapi seluruh data identitas pengisi.');
                    }
                }

                if ($slugForResponse === 'pengguna' && !$unitId) {
                    throw new InvalidArgumentException('Asal Bidang Ilmu Lulusan (Program Studi) wajib dipilih.');
                }
            }

            if ($slugForResponse === 'mahasiswa' && !empty($input['nim_pengisi'])) {
                $identity['nim_pengisi'] = trim($input['nim_pengisi']);
            }

            $responseId = $this->repository()->createResponse($typeId, $unitId, $saran, $identity, $surveyYear);

            foreach ($answers as $questionId => $score) {

                $score = (int) $score;

                if ($score < 1 || $score > 5) {
                    continue;
                }

                $this->repository()->saveAnswer($responseId, (int) $questionId, $score);
            }

            return $this->success(null, 'Terima kasih, survey Anda berhasil dikirim.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getProdiUnits(): array
    {
        return $this->success($this->repository()->getProdiUnits());
    }

    public function getOverallStats(int $typeId, int $year = 0, int $unitId = 0): array
    {
        return $this->success($this->repository()->getOverallStats($typeId, $year, $unitId));
    }

    public function getCategoryScores(int $typeId, int $year = 0, int $unitId = 0): array
    {
        return $this->success($this->repository()->getCategoryScores($typeId, $year, $unitId));
    }

    public function getQuestionScores(int $typeId, int $year = 0, int $unitId = 0): array
    {
        return $this->success($this->repository()->getQuestionScores($typeId, $year, $unitId));
    }

    public function getSaranList(int $typeId, int $year = 0, int $limit = 50, int $offset = 0, int $unitId = 0): array
    {
        return $this->success($this->repository()->getSaranList($typeId, $year, $limit, $offset, $unitId));
    }

    public function countSaran(int $typeId, int $year = 0, int $unitId = 0): array
    {
        return $this->success($this->repository()->countSaran($typeId, $year, $unitId));
    }

    public function getAvailableYears(int $typeId, int $unitId = 0): array
    {
        return $this->success($this->repository()->getAvailableYears($typeId, $unitId));
    }

    public function isLayananBased(int $typeId): bool
    {
        return $this->repository()->isLayananBased($typeId);
    }

    public function getScaleConfig(int $typeId): array
    {
        return $this->repository()->getScaleConfig($typeId);
    }

    public function getLayananForm(int $typeId): array
    {
        return $this->success($this->repository()->getLayananWithAspekAndQuestions($typeId));
    }

    public function getLayananScores(int $typeId, int $year = 0, int $unitId = 0): array
    {
        $layananList = $this->repository()->getLayananList($typeId);

        foreach ($layananList as &$layanan) {

            $aspekScores = $this->repository()->getAspekScoresByLayanan((int) $layanan['id'], $year, $unitId);

            foreach ($aspekScores as &$aspek) {
                $aspek['avg_score'] = $aspek['avg_score'] !== null ? round((float) $aspek['avg_score'], 2) : null;
                $aspek['questions'] = $this->repository()->getQuestionScoresByAspek((int) $aspek['id'], $year, $unitId);

                foreach ($aspek['questions'] as &$q) {
                    $q['avg_score'] = $q['avg_score'] !== null ? round((float) $q['avg_score'], 2) : null;
                }
                unset($q);
            }
            unset($aspek);

            $layanan['aspek_scores'] = $aspekScores;

            $validScores = array_values(array_filter(
                array_map(fn($a) => $a['avg_score'], $aspekScores),
                fn($v) => $v !== null
            ));

            $layanan['skor_akhir'] = !empty($validScores)
                ? round(array_sum($validScores) / count($validScores), 2)
                : 0;
        }
        unset($layanan);

        return $this->success($layananList);
    }

    public function getLayananRecapTable(int $typeId, int $year = 0, int $unitId = 0): array
    {
        $layananList = $this->repository()->getLayananList($typeId);

        foreach ($layananList as &$layanan) {

            $aspekScores = $this->repository()->getAspekScoresByLayanan((int) $layanan['id'], $year, $unitId);

            foreach ($aspekScores as &$aspek) {
                $aspek['distribusi'] = $this->repository()->getAspekDistribution((int) $aspek['id'], $year, $unitId);
            }
            unset($aspek);

            $layanan['aspek_scores'] = $aspekScores;
        }
        unset($layanan);

        return $this->success($layananList);
    }

public function getCategoryRecapTable(int $typeId, int $year = 0, int $unitId = 0): array
    {
        $categories = $this->repository()->getCategoriesList($typeId);

        foreach ($categories as &$cat) {

            $cat['distribusi'] = $this->repository()->getAspekDistribution((int) $cat['id'], $year, $unitId);

            $questions = $this->repository()->getQuestionsByCategory((int) $cat['id']);

            foreach ($questions as &$q) {
                $q['distribusi'] = $this->repository()->getQuestionDistribution((int) $q['id'], $year, $unitId);
            }
            unset($q);

            $cat['questions'] = $questions;
        }
        unset($cat);

        return $this->success($categories);
    }
    public function getIdentityResponses(int $typeId, int $year = 0, int $unitId = 0, int $limit = 100, int $offset = 0): array
    {
        return $this->success($this->repository()->getIdentityResponses($typeId, $year, $unitId, $limit, $offset));
    }
    /*
    |--------------------------------------------------------------------------
    | KELOLA PERTANYAAN — Layanan
    |--------------------------------------------------------------------------
    */

    public function saveLayanan(int $typeId, int $id, string $name): array
    {
        try {

            $name = trim($name);

            if ($name === '') {
                throw new InvalidArgumentException('Nama Layanan wajib diisi.');
            }

            if ($id > 0) {
                $this->repository()->updateLayanan($id, $name);
                return $this->success(['id' => $id], 'Layanan berhasil diperbarui.');
            }

            $newId = $this->repository()->createLayanan($typeId, $name);

            return $this->success(['id' => $newId], 'Layanan berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteLayanan(int $id): array
    {
        $this->repository()->deleteLayanan($id);

        return $this->success(null, 'Layanan berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | KELOLA PERTANYAAN — Kategori / Aspek
    |--------------------------------------------------------------------------
    */

    public function saveCategory(int $typeId, ?int $layananId, int $id, string $name): array
    {
        try {

            $name = trim($name);

            if ($name === '') {
                throw new InvalidArgumentException('Nama Kategori/Aspek wajib diisi.');
            }

            if ($id > 0) {
                $this->repository()->updateCategory($id, $name);
                return $this->success(['id' => $id], 'Kategori/Aspek berhasil diperbarui.');
            }

            $newId = $this->repository()->createCategory($typeId, $layananId, $name);

            return $this->success(['id' => $newId], 'Kategori/Aspek berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteCategory(int $id): array
    {
        $this->repository()->deleteCategory($id);

        return $this->success(null, 'Kategori/Aspek berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | KELOLA PERTANYAAN — Pertanyaan
    |--------------------------------------------------------------------------
    */

    public function saveQuestion(int $categoryId, int $id, string $questionText): array
    {
        try {

            $questionText = trim($questionText);

            if ($questionText === '') {
                throw new InvalidArgumentException('Teks pertanyaan wajib diisi.');
            }

            if ($id > 0) {
                $this->repository()->updateQuestion($id, $questionText);
                return $this->success(['id' => $id], 'Pertanyaan berhasil diperbarui.');
            }

            $newId = $this->repository()->createQuestion($categoryId, $questionText);

            return $this->success(['id' => $newId], 'Pertanyaan berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteQuestion(int $id): array
    {
        $this->repository()->deleteQuestion($id);

        return $this->success(null, 'Pertanyaan berhasil dihapus.');
    }

    /*
    |--------------------------------------------------------------------------
    | KELOLA PERTANYAAN — Ambil struktur lengkap
    |--------------------------------------------------------------------------
    */

    public function getManageTree(int $typeId): array
    {
        if ($this->isLayananBased($typeId)) {
            return $this->success($this->repository()->getLayananWithAspekAndQuestions($typeId));
        }

        return $this->success($this->repository()->getCategoriesWithQuestions($typeId));
    }
    public function updateCategoryResponsibleUnit(int $categoryId, ?int $unitId): array
    {
        $this->repository()->updateCategoryResponsibleUnit($categoryId, $unitId);

        return $this->success(null, 'Unit Penanggung Jawab berhasil disimpan.');
    }

    public function getAllUnits(): array
    {
        return $this->success($this->repository()->getAllUnits());
    }

    public function generateFollowUp(int $typeId, int $year, int $unitId = 0): array
    {
        try {

            $slug = $this->repository()->getSlugById($typeId);
            $periodId = $this->repository()->getActivePeriodId();

            if (!$periodId) {
                throw new InvalidArgumentException('Tidak ada Periode Audit berstatus Aktif. Aktifkan salah satu Periode terlebih dahulu di Master Periode Audit.');
            }

            $isLayananBased = $this->isLayananBased($typeId);
            $requiresUnitSelection = in_array($slug, ['mahasiswa', 'pengguna'], true);

            if ($requiresUnitSelection && $unitId <= 0) {
                throw new InvalidArgumentException('Silakan pilih Program Studi pada filter terlebih dahulu sebelum membuat Tindak Lanjut untuk jenis survey ini.');
            }

            require_once __DIR__ . '/../rtl/repository.php';
            require_once __DIR__ . '/../rtl/service.php';
            require_once __DIR__ . '/../ptp/repository.php';
            require_once __DIR__ . '/../ptp/service.php';

            $rtmRepo = new RtmRepository($this->repository()->getConnection());
            $rtmService = new RtmService($rtmRepo);

            $ptpRepo = new PtpRepository($this->repository()->getConnection());
            $ptpService = new PtpService($ptpRepo);

            $categoriesToProcess = [];

            if ($isLayananBased) {

                $layananList = $this->repository()->getLayananList($typeId);

                foreach ($layananList as $layanan) {

                    $aspekScores = $this->repository()->getAspekScoresByLayanan((int) $layanan['id'], $year, $unitId);

                    foreach ($aspekScores as $aspek) {
                        $dist = $this->repository()->getAspekDistribution((int) $aspek['id'], $year, $unitId);
                        $categoriesToProcess[] = [
                            'id'          => $aspek['id'],
                            'name'        => 'Layanan ' . $layanan['name'] . ' - ' . $aspek['name'],
                            'distribusi'  => $dist,
                            'target_unit' => $unitId,
                        ];
                    }
                }

            } else {

                $categories = $this->repository()->getCategoriesList($typeId);

                foreach ($categories as $cat) {

                    $dist = $this->repository()->getAspekDistribution((int) $cat['id'], $year, $unitId);
                    $targetUnit = $requiresUnitSelection ? $unitId : (int) ($cat['responsible_unit_id'] ?? 0);

                    $categoriesToProcess[] = [
                        'id'          => $cat['id'],
                        'name'        => $cat['name'],
                        'distribusi'  => $dist,
                        'target_unit' => $targetUnit,
                    ];
                }
            }

            $createdRtl = 0;
            $createdPtp = 0;
            $skippedNoUnit = 0;

            foreach ($categoriesToProcess as $cat) {

                if (empty($cat['target_unit'])) {
                    $skippedNoUnit++;
                    continue;
                }

                if ($cat['distribusi']['total'] <= 0) {
                    continue;
                }

                $puas = $cat['distribusi']['sangat_baik'] + $cat['distribusi']['baik'];
                $targetUnitId = (int) $cat['target_unit'];

                if ($puas < 60) {

                    $meetingId = $rtmService->findOrCreateSurveyMeeting($targetUnitId, $periodId, $year);

                    if (!$rtmService->existsSurveyActionPlan($meetingId, (int) $cat['id'], $year)) {

                        $rtmService->createActionPlanFromSurvey([
                            'rtm_meeting_id'     => $meetingId,
                            'survey_type_id'     => $typeId,
                            'survey_category_id' => $cat['id'],
                            'survey_year'        => $year,
                            'importance'         => 'Important',
                            'urgency'            => 'Urgent',
                            'activity'           => 'Tindak lanjuti hasil Survey Kepuasan pada aspek "' . $cat['name'] . '" (tingkat kepuasan ' . round($puas, 1) . '%).',
                        ]);

                        $createdRtl++;
                    }
                }
            }

            $message = "Berhasil: {$createdRtl} RTL baru dibuat di RTM Pengendalian.";

            if ($skippedNoUnit > 0) {
                $message .= " ({$skippedNoUnit} Kategori dilewati karena belum ada Unit Penanggung Jawab — atur dulu di menu Kelola Pertanyaan Survey.)";
            }

            return $this->success([
                'created_rtl' => $createdRtl,
                'created_ptp' => $createdPtp,
                'skipped'     => $skippedNoUnit,
            ], $message);

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    private function getRTabel(int $df): float
    {
        // Tabel r Product Moment, taraf signifikansi 5% (dua sisi)
        $table = [
            1=>0.997,2=>0.950,3=>0.878,4=>0.811,5=>0.754,6=>0.707,7=>0.666,8=>0.632,9=>0.602,10=>0.576,
            11=>0.553,12=>0.532,13=>0.514,14=>0.497,15=>0.482,16=>0.468,17=>0.456,18=>0.444,19=>0.433,20=>0.423,
            21=>0.413,22=>0.404,23=>0.396,24=>0.388,25=>0.381,26=>0.374,27=>0.367,28=>0.361,29=>0.355,30=>0.349,
            35=>0.325,40=>0.304,45=>0.288,50=>0.273,60=>0.250,70=>0.232,80=>0.217,90=>0.205,100=>0.195,
        ];

        if (isset($table[$df])) {
            return $table[$df];
        }

        if ($df < 1) {
            return 0.997;
        }

        if ($df > 100) {
            // Pendekatan untuk sampel besar (n > 102)
            return round(1.96 / sqrt($df + 1), 3);
        }

        // Interpolasi linear antar nilai terdekat di tabel
        $keys = array_keys($table);
        sort($keys);

        $lower = null;
        $upper = null;

        foreach ($keys as $k) {
            if ($k <= $df) $lower = $k;
            if ($k >= $df && $upper === null) $upper = $k;
        }

        if ($lower === null) return $table[$keys[0]];
        if ($upper === null) return $table[end($keys)];
        if ($lower === $upper) return $table[$lower];

        $fraction = ($df - $lower) / ($upper - $lower);

        return round($table[$lower] + ($table[$upper] - $table[$lower]) * $fraction, 3);
    }

    private function pearsonCorrelation(array $x, array $y): ?float
    {
        $n = count($x);

        if ($n < 3 || $n !== count($y)) {
            return null;
        }

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $numerator = 0;
        $sumSqX = 0;
        $sumSqY = 0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $numerator += $dx * $dy;
            $sumSqX += $dx * $dx;
            $sumSqY += $dy * $dy;
        }

        $denominator = sqrt($sumSqX * $sumSqY);

        if ($denominator == 0) {
            return null;
        }

        return $numerator / $denominator;
    }

    private function variance(array $values): float
    {
        $n = count($values);

        if ($n < 2) {
            return 0;
        }

        $mean = array_sum($values) / $n;
        $sumSq = 0;

        foreach ($values as $v) {
            $sumSq += ($v - $mean) ** 2;
        }

        return $sumSq / $n;
    }

    public function getValidityReliability(int $typeId, int $year, int $unitId = 0): array
    {
        $questions = $this->repository()->getFlatQuestionList($typeId);
        $matrix = $this->repository()->getRawResponseMatrix($typeId, $year, $unitId);

        // Cuma pakai responden yang menjawab SEMUA pertanyaan (data lengkap)
        $completeResponses = [];

        foreach ($matrix as $responseId => $answers) {

            $isComplete = true;

            foreach ($questions as $q) {
                if (!isset($answers[$q['id']])) {
                    $isComplete = false;
                    break;
                }
            }

            if ($isComplete) {
                $completeResponses[$responseId] = $answers;
            }
        }

        $n = count($completeResponses);

        if ($n < 3 || empty($questions)) {
            return $this->success([
                'n'              => $n,
                'df'             => max(0, $n - 2),
                'r_tabel'        => null,
                'items'          => [],
                'cronbach_alpha' => null,
                'reliable'       => null,
                'insufficient'   => true,
            ]);
        }

        $df = $n - 2;
        $rTabel = $this->getRTabel($df);

        // Susun skor total per responden
        $totalScores = [];
        foreach ($completeResponses as $responseId => $answers) {
            $totalScores[$responseId] = array_sum($answers);
        }

        $itemResults = [];
        $itemVariances = [];

        foreach ($questions as $q) {

            $itemScores = [];
            $totals = [];

            foreach ($completeResponses as $responseId => $answers) {
                $itemScores[] = $answers[$q['id']];
                $totals[] = $totalScores[$responseId];
            }

            $r = $this->pearsonCorrelation($itemScores, $totals);
            $rRounded = $r !== null ? round($r, 3) : null;

            $itemResults[] = [
                'question_id'   => $q['id'],
                'question_text' => $q['question_text'],
                'category_name' => $q['category_name'],
                'r_hitung'      => $rRounded,
                'valid'         => $rRounded !== null ? ($rRounded > $rTabel) : null,
            ];

            $itemVariances[] = $this->variance($itemScores);
        }

        $k = count($questions);
        $sumItemVariance = array_sum($itemVariances);
        $totalVariance = $this->variance(array_values($totalScores));

        $cronbachAlpha = null;

        if ($k > 1 && $totalVariance > 0) {
            $cronbachAlpha = round(($k / ($k - 1)) * (1 - ($sumItemVariance / $totalVariance)), 3);
        }

        $reliableLabel = null;

        if ($cronbachAlpha !== null) {
            if ($cronbachAlpha >= 0.9) $reliableLabel = 'Sangat Reliabel';
            elseif ($cronbachAlpha >= 0.8) $reliableLabel = 'Reliabel';
            elseif ($cronbachAlpha >= 0.7) $reliableLabel = 'Cukup Reliabel';
            elseif ($cronbachAlpha >= 0.6) $reliableLabel = 'Kurang Reliabel';
            else $reliableLabel = 'Tidak Reliabel';
        }

        return $this->success([
            'n'              => $n,
            'df'             => $df,
            'r_tabel'        => $rTabel,
            'items'          => $itemResults,
            'cronbach_alpha' => $cronbachAlpha,
            'reliable'       => $reliableLabel,
            'insufficient'   => false,
        ]);
    }
}