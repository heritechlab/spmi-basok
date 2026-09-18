<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class LedReportRepository extends BaseRepository
{
    protected string $table = 'desk_evaluation_items';

    public function getUnitInfo(int $unitId): ?array
    {
        $stmt = $this->prepare("SELECT id, code, name, head_name FROM units WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getPeriodInfo(int $periodId): ?array
    {
        $stmt = $this->prepare("SELECT id, period_name, year FROM audit_periods WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $periodId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function getAssignmentIdsForUnitPeriod(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT id FROM audit_assignments
            WHERE auditee_id = ? AND period_id = ?
        ");
        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return array_map('intval', array_column($this->fetchAll($stmt), 'id'));
    }

    public function getKriteria(): array
    {
        $stmt = $this->prepare("SELECT id, name FROM acc_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAssignedStandardIds(array $assignmentIds): array
    {
        if (empty($assignmentIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($assignmentIds), '?'));
        $types = str_repeat('i', count($assignmentIds));

        $stmt = $this->prepare("SELECT DISTINCT standard_id FROM assignment_standards WHERE assignment_id IN ({$placeholders})");
        $stmt->bind_param($types, ...$assignmentIds);
        $this->execute($stmt);

        $ids = array_map('intval', array_column($this->fetchAll($stmt), 'standard_id'));

        if (!empty($ids)) {
            return $ids;
        }

        $stmtAll = $this->prepare("SELECT id FROM standards WHERE is_active = 1");
        $this->execute($stmtAll);

        return array_map('intval', array_column($this->fetchAll($stmtAll), 'id'));
    }

    public function getStandardsByKriteria(int $criteriaId, array $allowedStandardIds): array
    {
        if (empty($allowedStandardIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($allowedStandardIds), '?'));
        $types = 'i' . str_repeat('i', count($allowedStandardIds));
        $params = array_merge([$criteriaId], $allowedStandardIds);

        $stmt = $this->prepare("
            SELECT id, code, name
            FROM standards
            WHERE prodi_criteria_id = ? AND is_active = 1 AND id IN ({$placeholders})
            ORDER BY code ASC
        ");
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getIndicatorsByStandard(int $standardId, int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT ai.id, ai.item_code, ai.statement, ai.indicator, ai.target
            FROM audit_indicators ai
            LEFT JOIN standard_statements ss ON ss.id = ai.statement_id
            WHERE ai.standard_id = ? AND ai.status = 1
              AND (ss.owner_type IS NULL OR ss.owner_type = 'prodi')
            ORDER BY ai.sort_order ASC, ai.item_code ASC
        ");
        $stmt->bind_param("i", $standardId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getEntriesAcrossAssignments(array $indicatorIds, array $assignmentIds): array
    {
        if (empty($indicatorIds) || empty($assignmentIds)) {
            return [];
        }

        $phInd = implode(',', array_fill(0, count($indicatorIds), '?'));
        $phAsg = implode(',', array_fill(0, count($assignmentIds), '?'));
        $types = str_repeat('i', count($indicatorIds)) . str_repeat('i', count($assignmentIds));
        $params = array_merge($indicatorIds, $assignmentIds);

        $stmt = $this->prepare("
            SELECT id, audit_indicator_id, notes, capaian_realisasi
            FROM desk_evaluation_items
            WHERE audit_indicator_id IN ({$phInd}) AND assignment_id IN ({$phAsg})
            ORDER BY updated_at DESC
        ");
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);
        $result = [];

        foreach ($rows as $row) {

            $indId = (int) $row['audit_indicator_id'];

            if (isset($result[$indId])) {
                continue;
            }

            $stmtDoc = $this->prepare("
                SELECT document_file, document_original_name, link_url
                FROM desk_evaluation_documents
                WHERE desk_evaluation_item_id = ?
            ");
            $stmtDoc->bind_param("i", $row['id']);
            $this->execute($stmtDoc);

            $row['documents'] = $this->fetchAll($stmtDoc);

            $result[$indId] = $row;
        }

        return $result;
    }

    /* Narasi bebas (Kata Pengantar, Pendahuluan, Kesimpulan) */

    public function getNarratives(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT section, content FROM led_report_narratives
            WHERE unit_id = ? AND period_id = ?
        ");
        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        $result = [];
        foreach ($this->fetchAll($stmt) as $row) {
            $result[$row['section']] = $row['content'];
        }

        return $result;
    }

    public function saveNarrative(int $unitId, int $periodId, string $section, string $content, int $updatedBy): void
    {
        $stmt = $this->prepare("
            INSERT INTO led_report_narratives (unit_id, period_id, section, content, updated_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE content = VALUES(content), updated_by = VALUES(updated_by), updated_at = NOW()
        ");
        $stmt->bind_param("iissi", $unitId, $periodId, $section, $content, $updatedBy);
        $this->execute($stmt);
    }

    /* Analisis Kekuatan-Kelemahan per Kriteria */

    public function getKriteriaAnalysis(int $unitId, int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT criteria_id, kekuatan, kelemahan FROM led_report_kriteria_analysis
            WHERE unit_id = ? AND period_id = ?
        ");
        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        $result = [];
        foreach ($this->fetchAll($stmt) as $row) {
            $result[(int) $row['criteria_id']] = $row;
        }

        return $result;
    }

    public function saveKriteriaAnalysis(int $unitId, int $periodId, int $criteriaId, string $kekuatan, string $kelemahan, int $updatedBy): void
    {
        $stmt = $this->prepare("
            INSERT INTO led_report_kriteria_analysis (unit_id, period_id, criteria_id, kekuatan, kelemahan, updated_by)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE kekuatan = VALUES(kekuatan), kelemahan = VALUES(kelemahan), updated_by = VALUES(updated_by), updated_at = NOW()
        ");
        $stmt->bind_param("iiissi", $unitId, $periodId, $criteriaId, $kekuatan, $kelemahan, $updatedBy);
        $this->execute($stmt);
    }

    /* Tanda Tangan (pakai ulang tabel laporan_ami_signatures) */

    public function getSignature(int $unitId, int $periodId): ?array
    {
        $stmt = $this->prepare("
            SELECT * FROM laporan_ami_signatures
            WHERE report_type = 'led' AND unit_id = ? AND period_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("ii", $unitId, $periodId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }
}