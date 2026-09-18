<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class SurveyValidityRepository extends BaseRepository
{
    protected string $table = 'survey_validity_tests';

    public function getTest(int $typeId): ?array
    {
        $stmt = $this->prepare("SELECT * FROM survey_validity_tests WHERE type_id = ? LIMIT 1");
        $stmt->bind_param("i", $typeId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getItems(int $testId): array
    {
        $stmt = $this->prepare("
            SELECT vi.id, vi.question_id, vi.r_hitung, vi.is_valid, sq.question_text, sc.name AS category_name
            FROM survey_validity_items vi
            JOIN survey_questions sq ON sq.id = vi.question_id
            JOIN survey_categories sc ON sc.id = sq.category_id
            WHERE vi.test_id = ?
            ORDER BY sc.sort_order ASC, sq.sort_order ASC
        ");
        $stmt->bind_param("i", $testId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function upsertTest(int $typeId, array $data): int
    {
        $existing = $this->getTest($typeId);

        if ($existing) {

            $stmt = $this->prepare("
                UPDATE survey_validity_tests
                SET n_responden = ?, r_tabel = ?, cronbach_alpha = ?, reliability_label = ?, catatan = ?, updated_by = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                "iddssii",
                $data['n_responden'], $data['r_tabel'], $data['cronbach_alpha'],
                $data['reliability_label'], $data['catatan'], $data['updated_by'],
                $existing['id']
            );
            $this->execute($stmt);

            return (int) $existing['id'];
        }

        $stmt = $this->prepare("
            INSERT INTO survey_validity_tests (type_id, n_responden, r_tabel, cronbach_alpha, reliability_label, catatan, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiddssi",
            $typeId, $data['n_responden'], $data['r_tabel'], $data['cronbach_alpha'],
            $data['reliability_label'], $data['catatan'], $data['updated_by']
        );
        $this->execute($stmt);

        return $this->insertId();
    }

    public function saveItem(int $testId, int $questionId, ?float $rHitung, ?int $isValid): void
    {
        $stmt = $this->prepare("
            INSERT INTO survey_validity_items (test_id, question_id, r_hitung, is_valid)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE r_hitung = VALUES(r_hitung), is_valid = VALUES(is_valid)
        ");
        $stmt->bind_param("iidi", $testId, $questionId, $rHitung, $isValid);
        $this->execute($stmt);
    }
}