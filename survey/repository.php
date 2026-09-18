<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class SurveyRepository extends BaseRepository
{
    protected string $table = 'survey_responses';

    public function getAllTypes(): array
    {
        $stmt = $this->prepare("SELECT id, slug, name FROM survey_types ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getTypeBySlug(string $slug): ?array
    {
        $stmt = $this->prepare("SELECT id, slug, name, requires_identity FROM survey_types WHERE slug = ? LIMIT 1");
        $stmt->bind_param("s", $slug);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getSlugById(int $typeId): string
    {
        $stmt = $this->prepare("SELECT slug FROM survey_types WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return $this->fetchOne($stmt)['slug'] ?? '';
    }

    public function getActivePeriodId(): ?int
    {
        $stmt = $this->prepare("SELECT id FROM audit_periods WHERE status = 'Aktif' LIMIT 1");
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return $row ? (int) $row['id'] : null;
    }

public function getCategoriesWithQuestions(int $typeId): array
    {
        $stmt = $this->prepare("
            SELECT id, name, responsible_unit_id FROM survey_categories WHERE type_id = ? AND is_active = 1 ORDER BY sort_order ASC
        ");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);
        $categories = $this->fetchAll($stmt);

        foreach ($categories as &$cat) {

            $stmtQ = $this->prepare("
                SELECT id, question_text FROM survey_questions
                WHERE category_id = ? AND is_active = 1
                ORDER BY sort_order ASC
            ");
            $stmtQ->bind_param("i", $cat['id']);
            $this->execute($stmtQ);
            $cat['questions'] = $this->fetchAll($stmtQ);
        }
        unset($cat);

        return $categories;
    }

public function createResponse(int $typeId, ?int $unitId, string $saran, array $identity = [], int $surveyYear = 0): int
    {
        $columns = ['type_id', 'unit_id', 'saran', 'survey_year'];
        $placeholders = ['?', '?', '?', '?'];
        $types = 'iisi';
        $params = [$typeId, $unitId, $saran, $surveyYear ?: (int) date('Y')];

        foreach ($identity as $columnName => $value) {
            $columns[] = $columnName;
            $placeholders[] = '?';
            $types .= 's';
            $params[] = $value;
        }

        $sql = "INSERT INTO survey_responses (" . implode(', ', $columns) . ", submitted_at)
                VALUES (" . implode(', ', $placeholders) . ", NOW())";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function saveAnswer(int $responseId, int $questionId, int $score): void
    {
        $stmt = $this->prepare("
            INSERT INTO survey_response_answers (response_id, question_id, score)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iii", $responseId, $questionId, $score);
        $this->execute($stmt);
    }

public function getOverallStats(int $typeId, int $year = 0, int $unitId = 0): array
    {
        $sql = "SELECT COUNT(*) AS total FROM survey_responses WHERE type_id = ?";
        $types = "i";
        $params = [$typeId];

        if ($year > 0) {
            $sql .= " AND survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);
        $total = (int) ($this->fetchOne($stmt)['total'] ?? 0);

        $sql2 = "
            SELECT AVG(sra.score) AS avg_score
            FROM survey_response_answers sra
            JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sr.type_id = ?
        ";
        $types2 = "i";
        $params2 = [$typeId];

        if ($year > 0) {
            $sql2 .= " AND sr.survey_year = ?";
            $types2 .= "i";
            $params2[] = $year;
        }

        if ($unitId > 0) {
            $sql2 .= " AND sr.unit_id = ?";
            $types2 .= "i";
            $params2[] = $unitId;
        }

        $stmt2 = $this->prepare($sql2);
        $stmt2->bind_param($types2, ...$params2);
        $this->execute($stmt2);
        $avgScore = round((float) ($this->fetchOne($stmt2)['avg_score'] ?? 0), 2);

        return [
            'total_responden' => $total,
            'rata_rata_skor'  => $avgScore,
        ];
    }

public function getCategoryScores(int $typeId, int $year = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT
                sc.id, sc.name,
                AVG(sra.score) AS avg_score,
                COUNT(sra.id) AS total_jawaban
            FROM survey_categories sc
            JOIN survey_questions sq ON sq.category_id = sc.id
            LEFT JOIN survey_response_answers sra ON sra.question_id = sq.id
            LEFT JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sc.is_active = 1 AND sc.type_id = ?
        ";
        $types = "i";
        $params = [$typeId];

        if ($year > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.survey_year = ?)";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.unit_id = ?)";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " GROUP BY sc.id, sc.name ORDER BY sc.sort_order ASC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getQuestionScores(int $typeId, int $year = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT
                sq.id, sq.question_text, sc.name AS category_name,
                AVG(sra.score) AS avg_score,
                COUNT(sra.id) AS total_jawaban
            FROM survey_questions sq
            JOIN survey_categories sc ON sc.id = sq.category_id
            LEFT JOIN survey_response_answers sra ON sra.question_id = sq.id
            LEFT JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sq.is_active = 1 AND sc.type_id = ?
        ";
        $types = "i";
        $params = [$typeId];

        if ($year > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.survey_year = ?)";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.unit_id = ?)";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " GROUP BY sq.id, sq.question_text, sc.name ORDER BY sc.sort_order ASC, sq.sort_order ASC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getSaranList(int $typeId, int $year = 0, int $limit = 50, int $offset = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT sr.id, sr.saran, sr.submitted_at, u.name AS unit_name
            FROM survey_responses sr
            LEFT JOIN units u ON u.id = sr.unit_id
            WHERE sr.type_id = ? AND sr.saran IS NOT NULL AND sr.saran <> ''
        ";
        $types = "i";
        $params = [$typeId];

        if ($year > 0) {
            $sql .= " AND sr.survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND sr.unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " ORDER BY sr.submitted_at DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function countSaran(int $typeId, int $year = 0, int $unitId = 0): int
    {
        $sql = "SELECT COUNT(*) AS total FROM survey_responses WHERE type_id = ? AND saran IS NOT NULL AND saran <> ''";
        $types = "i";
        $params = [$typeId];

        if ($year > 0) {
            $sql .= " AND survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return (int) ($this->fetchOne($stmt)['total'] ?? 0);
    }
public function getAvailableYears(int $typeId, int $unitId = 0): array
    {
        $sql = "SELECT DISTINCT survey_year AS tahun FROM survey_responses WHERE type_id = ?";
        $types = "i";
        $params = [$typeId];

        if ($unitId > 0) {
            $sql .= " AND unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " ORDER BY tahun DESC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return array_column($this->fetchAll($stmt), 'tahun');
    }
    public function isLayananBased(int $typeId): bool
    {
        $stmt = $this->prepare("SELECT COUNT(*) AS total FROM survey_layanan WHERE type_id = ?");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return ((int) ($this->fetchOne($stmt)['total'] ?? 0)) > 0;
    }

    public function getScaleConfig(int $typeId): array
    {
        $stmt = $this->prepare("SELECT scale_max, scale_labels FROM survey_types WHERE id = ?");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        $max = (int) ($row['scale_max'] ?? 5);
        $labels = !empty($row['scale_labels']) ? explode(',', $row['scale_labels']) : [];

        return ['max' => $max, 'labels' => $labels];
    }

    public function getLayananWithAspekAndQuestions(int $typeId): array
    {
        $stmt = $this->prepare("SELECT id, name FROM survey_layanan WHERE type_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);
        $layananList = $this->fetchAll($stmt);

        foreach ($layananList as &$layanan) {

            $stmtAspek = $this->prepare("SELECT id, name FROM survey_categories WHERE layanan_id = ? AND is_active = 1 ORDER BY sort_order ASC");
            $stmtAspek->bind_param("i", $layanan['id']);
            $this->execute($stmtAspek);
            $aspekList = $this->fetchAll($stmtAspek);

            foreach ($aspekList as &$aspek) {

                $stmtQ = $this->prepare("SELECT id, question_text FROM survey_questions WHERE category_id = ? AND is_active = 1 ORDER BY sort_order ASC");
                $stmtQ->bind_param("i", $aspek['id']);
                $this->execute($stmtQ);
                $aspek['questions'] = $this->fetchAll($stmtQ);
            }
            unset($aspek);

            $layanan['aspek'] = $aspekList;
        }
        unset($layanan);

        return $layananList;
    }

 public function getAspekScoresByLayanan(int $layananId, int $year = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT sc.id, sc.name, AVG(sra.score) AS avg_score, COUNT(sra.id) AS total_jawaban
            FROM survey_categories sc
            JOIN survey_questions sq ON sq.category_id = sc.id
            LEFT JOIN survey_response_answers sra ON sra.question_id = sq.id
            LEFT JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sc.layanan_id = ? AND sc.is_active = 1
        ";
        $types = "i";
        $params = [$layananId];

        if ($year > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.survey_year = ?)";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.unit_id = ?)";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " GROUP BY sc.id, sc.name ORDER BY sc.sort_order ASC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getLayananList(int $typeId): array
    {
        $stmt = $this->prepare("SELECT id, name FROM survey_layanan WHERE type_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

 public function getQuestionScoresByAspek(int $aspekId, int $year = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT sq.id, sq.question_text, AVG(sra.score) AS avg_score, COUNT(sra.id) AS total_jawaban
            FROM survey_questions sq
            LEFT JOIN survey_response_answers sra ON sra.question_id = sq.id
            LEFT JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sq.category_id = ? AND sq.is_active = 1
        ";
        $types = "i";
        $params = [$aspekId];

        if ($year > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.survey_year = ?)";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND (sr.id IS NULL OR sr.unit_id = ?)";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " GROUP BY sq.id, sq.question_text ORDER BY sq.sort_order ASC";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getQuestionsByCategory(int $categoryId): array
    {
        $stmt = $this->prepare("SELECT id, question_text FROM survey_questions WHERE category_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->bind_param("i", $categoryId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getQuestionDistribution(int $questionId, int $year = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT sra.score, COUNT(*) AS jumlah
            FROM survey_response_answers sra
            JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sra.question_id = ?
        ";
        $types = "i";
        $params = [$questionId];

        if ($year > 0) {
            $sql .= " AND sr.survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND sr.unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " GROUP BY sra.score";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);

        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($rows as $row) {
            $score = (int) $row['score'];
            if (isset($counts[$score])) {
                $counts[$score] = (int) $row['jumlah'];
            }
        }

        $total = array_sum($counts);

        return [
            'total'        => $total,
            'sangat_baik'  => $total > 0 ? round(($counts[4] / $total) * 100, 1) : 0,
            'baik'         => $total > 0 ? round(($counts[3] / $total) * 100, 1) : 0,
            'cukup'        => $total > 0 ? round(($counts[2] / $total) * 100, 1) : 0,
            'kurang'       => $total > 0 ? round(($counts[1] / $total) * 100, 1) : 0,
        ];
    }
 public function getAspekDistribution(int $aspekId, int $year = 0, int $unitId = 0): array
    {
        $sql = "
            SELECT sra.score, COUNT(*) AS jumlah
            FROM survey_response_answers sra
            JOIN survey_questions sq ON sq.id = sra.question_id
            JOIN survey_responses sr ON sr.id = sra.response_id
            WHERE sq.category_id = ?
        ";
        $types = "i";
        $params = [$aspekId];

        if ($year > 0) {
            $sql .= " AND sr.survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND sr.unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " GROUP BY sra.score";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);

        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($rows as $row) {
            $score = (int) $row['score'];
            if (isset($counts[$score])) {
                $counts[$score] = (int) $row['jumlah'];
            }
        }

        $total = array_sum($counts);

        return [
            'total'        => $total,
            'sangat_baik'  => $total > 0 ? round(($counts[4] / $total) * 100, 1) : 0,
            'baik'         => $total > 0 ? round(($counts[3] / $total) * 100, 1) : 0,
            'cukup'        => $total > 0 ? round(($counts[2] / $total) * 100, 1) : 0,
            'kurang'       => $total > 0 ? round(($counts[1] / $total) * 100, 1) : 0,
        ];
    }
    public function getProdiUnits(): array
    {
        $stmt = $this->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAllUnits(): array
    {
        $stmt = $this->prepare("SELECT id, code, name FROM units ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function requiresIdentity(int $typeId): bool
    {
        $stmt = $this->prepare("SELECT requires_identity FROM survey_types WHERE id = ?");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return ((int) ($this->fetchOne($stmt)['requires_identity'] ?? 0)) === 1;
    }

public function getCategoriesList(int $typeId): array
    {
        $stmt = $this->prepare("
            SELECT id, name, responsible_unit_id FROM survey_categories
            WHERE type_id = ? AND layanan_id IS NULL AND is_active = 1
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD: Layanan
    |--------------------------------------------------------------------------
    */

    public function createLayanan(int $typeId, string $name): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM survey_layanan WHERE type_id = ?");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO survey_layanan (type_id, name, sort_order, is_active) VALUES (?, ?, ?, 1)");
        $stmt2->bind_param("isi", $typeId, $name, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateLayanan(int $id, string $name): void
    {
        $stmt = $this->prepare("UPDATE survey_layanan SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $this->execute($stmt);
    }

    public function deleteLayanan(int $id): void
    {
        $stmt = $this->prepare("UPDATE survey_layanan SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $stmtCat = $this->prepare("UPDATE survey_categories SET is_active = 0 WHERE layanan_id = ?");
        $stmtCat->bind_param("i", $id);
        $this->execute($stmtCat);
    }

    public function findLayananById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT id, type_id, name FROM survey_layanan WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD: Kategori / Aspek
    |--------------------------------------------------------------------------
    */

    public function createCategory(int $typeId, ?int $layananId, string $name): int
    {
        $sql = $layananId
            ? "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM survey_categories WHERE layanan_id = ?"
            : "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM survey_categories WHERE type_id = ? AND layanan_id IS NULL";

        $stmt = $this->prepare($sql);
        $stmt->bind_param("i", $layananId ?: $typeId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO survey_categories (type_id, layanan_id, name, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt2->bind_param("iisi", $typeId, $layananId, $name, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateCategoryResponsibleUnit(int $categoryId, ?int $unitId): void
    {
        $stmt = $this->prepare("UPDATE survey_categories SET responsible_unit_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $unitId, $categoryId);
        $this->execute($stmt);
    }

    public function updateCategory(int $id, string $name): void
    {
        $stmt = $this->prepare("UPDATE survey_categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        $this->execute($stmt);
    }

    public function deleteCategory(int $id): void
    {
        $stmt = $this->prepare("UPDATE survey_categories SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $stmtQ = $this->prepare("UPDATE survey_questions SET is_active = 0 WHERE category_id = ?");
        $stmtQ->bind_param("i", $id);
        $this->execute($stmtQ);
    }

    public function findCategoryById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT id, type_id, layanan_id, name FROM survey_categories WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD: Pertanyaan
    |--------------------------------------------------------------------------
    */

    public function createQuestion(int $categoryId, string $questionText): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM survey_questions WHERE category_id = ?");
        $stmt->bind_param("i", $categoryId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO survey_questions (category_id, question_text, sort_order, is_active) VALUES (?, ?, ?, 1)");
        $stmt2->bind_param("isi", $categoryId, $questionText, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateQuestion(int $id, string $questionText): void
    {
        $stmt = $this->prepare("UPDATE survey_questions SET question_text = ? WHERE id = ?");
        $stmt->bind_param("si", $questionText, $id);
        $this->execute($stmt);
    }

    public function deleteQuestion(int $id): void
    {
        $stmt = $this->prepare("UPDATE survey_questions SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }

    public function findQuestionById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT id, category_id, question_text FROM survey_questions WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }
public function getIdentityResponses(int $typeId, int $year = 0, int $unitId = 0, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT sr.id, sr.nama_instansi, sr.nama_pengisi, sr.email, sr.no_hp,
                   sr.jumlah_lulusan, sr.masa_kerja, sr.bidang_kerjasama, sr.jabatan, sr.lama_kerjasama,
                   sr.submitted_at, u.name AS unit_name
            FROM survey_responses sr
            LEFT JOIN units u ON u.id = sr.unit_id
            WHERE sr.type_id = ?
        ";
        $types = "i";
        $params = [$typeId];

        if ($year > 0) {
            $sql .= " AND sr.survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND sr.unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $sql .= " ORDER BY sr.submitted_at DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getFlatQuestionList(int $typeId): array
    {
        $stmt = $this->prepare("
            SELECT sq.id, sq.question_text, sc.name AS category_name
            FROM survey_questions sq
            JOIN survey_categories sc ON sc.id = sq.category_id
            WHERE sc.type_id = ? AND sq.is_active = 1 AND sc.is_active = 1
            ORDER BY sc.sort_order ASC, sq.sort_order ASC
        ");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getRawResponseMatrix(int $typeId, int $year, int $unitId = 0): array
    {
        $sql = "
            SELECT sra.response_id, sra.question_id, sra.score
            FROM survey_response_answers sra
            JOIN survey_responses sr ON sr.id = sra.response_id
            JOIN survey_questions sq ON sq.id = sra.question_id
            JOIN survey_categories sc ON sc.id = sq.category_id
            WHERE sr.type_id = ? AND sc.type_id = ?
        ";
        $types = "ii";
        $params = [$typeId, $typeId];

        if ($year > 0) {
            $sql .= " AND sr.survey_year = ?";
            $types .= "i";
            $params[] = $year;
        }

        if ($unitId > 0) {
            $sql .= " AND sr.unit_id = ?";
            $types .= "i";
            $params[] = $unitId;
        }

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[$row['response_id']][$row['question_id']] = (float) $row['score'];
        }

        return $matrix;
    }
}