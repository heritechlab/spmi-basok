<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class WorkspaceRepository extends BaseRepository
{
    protected string $table = 'audit_checklists';

    /*
    |--------------------------------------------------------------------------
    | Generate Checklist (sekali per assignment, kalau belum ada)
    |--------------------------------------------------------------------------
    */

    public function generateChecklist(int $assignmentId): void
    {
        $check = $this->prepare("SELECT COUNT(*) AS total FROM audit_checklists WHERE assignment_id = ?");
        $check->bind_param("i", $assignmentId);
        $this->execute($check);
        $row = $this->fetchOne($check);

        if ((int)($row['total'] ?? 0) > 0) {
            return;
        }

        /* Deteksi jenis Auditee (Prodi vs Unit spesifik non-Prodi) */

        $stmtAuditee = $this->prepare("
            SELECT a.auditee_id, u.type
            FROM audit_assignments a
            JOIN units u ON u.id = a.auditee_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmtAuditee->bind_param("i", $assignmentId);
        $this->execute($stmtAuditee);
        $auditeeInfo = $this->fetchOne($stmtAuditee);

        $auditeeId = (int) ($auditeeInfo['auditee_id'] ?? 0);
        $isProdi = ($auditeeInfo['type'] ?? '') === 'Program Studi';

        $stmtStdIds = $this->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");
        $stmtStdIds->bind_param("i", $assignmentId);
        $this->execute($stmtStdIds);
        $selectedStandardIds = array_map(fn($r) => (int) $r['standard_id'], $this->fetchAll($stmtStdIds));

        /* Filter kepemilikan Pernyataan Standar - hanya ambil Indikator yg relevan utk Auditee ini */

        $ownerFilter = $isProdi
            ? "AND (ss.owner_type IS NULL OR ss.owner_type = 'prodi')"
            : "AND ss.owner_type = 'unit' AND ss.owner_unit_id = " . $auditeeId;

        if (!empty($selectedStandardIds)) {
            $placeholders = implode(',', array_fill(0, count($selectedStandardIds), '?'));
            $sql = "
                SELECT ai.id, ai.standard_id
                FROM audit_indicators ai
                LEFT JOIN standard_statements ss ON ss.id = ai.statement_id
                WHERE ai.status = 1 AND ai.standard_id IN ($placeholders)
                  {$ownerFilter}
                ORDER BY ai.item_code ASC
            ";
            $stmt = $this->connection()->prepare($sql);
            $types = str_repeat('i', count($selectedStandardIds));
            $stmt->bind_param($types, ...$selectedStandardIds);
            $stmt->execute();
            $indicators = $stmt->get_result();
        } else {
            $sql = "
                SELECT ai.id, ai.standard_id
                FROM audit_indicators ai
                LEFT JOIN standard_statements ss ON ss.id = ai.statement_id
                WHERE ai.status = 1
                  {$ownerFilter}
                ORDER BY ai.item_code ASC
            ";
            $indicators = $this->connection()->query($sql);
        }

        $order = 1;

        $stmt = $this->prepare("
            INSERT INTO audit_checklists
                (assignment_id, standard_id, audit_indicator_id, order_number, status, progress_percent, created_at)
            VALUES
                (?, ?, ?, ?, 'Belum', 0, NOW())
        ");

        while ($indicator = $indicators->fetch_assoc()) {
            $standardId = (int) $indicator['standard_id'];
            $indicatorId = (int) $indicator['id'];

            $stmt->bind_param("iiii", $assignmentId, $standardId, $indicatorId, $order);

            $this->execute($stmt);

            $order++;
        }
    }

        public function syncChecklistWithStandards(int $assignmentId): void
    {
        $stmtStdIds = $this->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");
        $stmtStdIds->bind_param("i", $assignmentId);
        $this->execute($stmtStdIds);
        $selectedStandardIds = array_map(fn($r) => (int) $r['standard_id'], $this->fetchAll($stmtStdIds));

        /* Hapus Checklist (+hasil-nya) utk Standar yg SUDAH TIDAK dipilih lagi */

        if (!empty($selectedStandardIds)) {

            $placeholders = implode(',', array_fill(0, count($selectedStandardIds), '?'));
            $types = 'i' . str_repeat('i', count($selectedStandardIds));
            $params = array_merge([$assignmentId], $selectedStandardIds);

            $stmtOldIds = $this->prepare("
                SELECT id FROM audit_checklists
                WHERE assignment_id = ? AND standard_id NOT IN ($placeholders)
            ");
            $stmtOldIds->bind_param($types, ...$params);
            $this->execute($stmtOldIds);
            $removedChecklistIds = array_map(fn($r) => (int) $r['id'], $this->fetchAll($stmtOldIds));

            if (!empty($removedChecklistIds)) {

                $ph2 = implode(',', array_fill(0, count($removedChecklistIds), '?'));
                $types2 = str_repeat('i', count($removedChecklistIds));

                $delResults = $this->prepare("DELETE FROM audit_checklist_results WHERE checklist_id IN ($ph2)");
                $delResults->bind_param($types2, ...$removedChecklistIds);
                $this->execute($delResults);

                $delChecklists = $this->prepare("DELETE FROM audit_checklists WHERE id IN ($ph2)");
                $delChecklists->bind_param($types2, ...$removedChecklistIds);
                $this->execute($delChecklists);
            }
        }

        /* Deteksi jenis Auditee, utk filter kepemilikan Indikator */

        $stmtAuditee = $this->prepare("
            SELECT a.auditee_id, u.type
            FROM audit_assignments a
            JOIN units u ON u.id = a.auditee_id
            WHERE a.id = ?
            LIMIT 1
        ");
        $stmtAuditee->bind_param("i", $assignmentId);
        $this->execute($stmtAuditee);
        $auditeeInfo = $this->fetchOne($stmtAuditee);

        $auditeeId = (int) ($auditeeInfo['auditee_id'] ?? 0);
        $isProdi = ($auditeeInfo['type'] ?? '') === 'Program Studi';

        $ownerFilter = $isProdi
            ? "AND (ss.owner_type IS NULL OR ss.owner_type = 'prodi')"
            : "AND ss.owner_type = 'unit' AND ss.owner_unit_id = " . $auditeeId;

        /* Ambil Indikator yg SEHARUSNYA ada (sesuai Standar terpilih + kepemilikan) */

        if (!empty($selectedStandardIds)) {
            $placeholders2 = implode(',', array_fill(0, count($selectedStandardIds), '?'));
            $sql = "
                SELECT ai.id, ai.standard_id
                FROM audit_indicators ai
                LEFT JOIN standard_statements ss ON ss.id = ai.statement_id
                WHERE ai.status = 1 AND ai.standard_id IN ($placeholders2)
                  {$ownerFilter}
                ORDER BY ai.item_code ASC
            ";
            $stmt = $this->connection()->prepare($sql);
            $types3 = str_repeat('i', count($selectedStandardIds));
            $stmt->bind_param($types3, ...$selectedStandardIds);
            $stmt->execute();
            $shouldExistIndicators = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $sql = "
                SELECT ai.id, ai.standard_id
                FROM audit_indicators ai
                LEFT JOIN standard_statements ss ON ss.id = ai.statement_id
                WHERE ai.status = 1
                  {$ownerFilter}
                ORDER BY ai.item_code ASC
            ";
            $shouldExistIndicators = $this->connection()->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        /* Ambil Indikator yg SUDAH ada di Checklist sekarang */

        $stmtExisting = $this->prepare("SELECT audit_indicator_id FROM audit_checklists WHERE assignment_id = ?");
        $stmtExisting->bind_param("i", $assignmentId);
        $this->execute($stmtExisting);
        $existingIndicatorIds = array_map(fn($r) => (int) $r['audit_indicator_id'], $this->fetchAll($stmtExisting));

        /* Cari nomor urut terakhir, utk lanjutan Indikator baru */

        $stmtMaxOrder = $this->prepare("SELECT MAX(order_number) AS max_order FROM audit_checklists WHERE assignment_id = ?");
        $stmtMaxOrder->bind_param("i", $assignmentId);
        $this->execute($stmtMaxOrder);
        $order = (int) ($this->fetchOne($stmtMaxOrder)['max_order'] ?? 0) + 1;

        $insertStmt = $this->prepare("
            INSERT INTO audit_checklists
                (assignment_id, standard_id, audit_indicator_id, order_number, status, progress_percent, created_at)
            VALUES
                (?, ?, ?, ?, 'Belum', 0, NOW())
        ");

        foreach ($shouldExistIndicators as $indicator) {

            $indicatorId = (int) $indicator['id'];

            if (in_array($indicatorId, $existingIndicatorIds, true)) {
                continue;
            }

            $standardId = (int) $indicator['standard_id'];

            $insertStmt->bind_param("iiii", $assignmentId, $standardId, $indicatorId, $order);
            $this->execute($insertStmt);

            $order++;
        }
    }

    public function isAllChecklistFilled(int $assignmentId): bool
    {
        $stmt = $this->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN r.audit_status IS NOT NULL AND r.audit_status <> '' THEN 1 ELSE 0 END) AS filled
            FROM audit_checklists c
            LEFT JOIN audit_checklist_results r ON r.checklist_id = c.id
            WHERE c.assignment_id = ?
        ");

        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        $total = (int) ($row['total'] ?? 0);
        $filled = (int) ($row['filled'] ?? 0);

        return $total > 0 && $filled >= $total;
    }

    public function getAssignmentNotificationInfo(int $assignmentId): ?array
    {
        $stmt = $this->prepare("
            SELECT a.assignment_number, a.lka_notified, u.name AS unit_name, u.email AS unit_email, u.head_name
            FROM audit_assignments a
            LEFT JOIN units u ON u.id = a.auditee_id
            WHERE a.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function markLkaNotified(int $assignmentId): void
    {
        $stmt = $this->prepare("UPDATE audit_assignments SET lka_notified = 1 WHERE id = ?");
        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);
    }

        /*
    |--------------------------------------------------------------------------
    | Desk Evaluation
    |--------------------------------------------------------------------------
    */

    public function getDeskEvaluation(int $assignmentId, int $standardId, int $indicatorId): ?array
    {
        $stmtItem = $this->prepare("
            SELECT id, notes
            FROM desk_evaluation_items
            WHERE assignment_id = ? AND audit_indicator_id = ?
            LIMIT 1
        ");

        $stmtItem->bind_param("ii", $assignmentId, $indicatorId);

        $this->execute($stmtItem);

        $item = $this->fetchOne($stmtItem);

        if (!$item) {
            return null;
        }

       $stmtDoc = $this->prepare("SELECT document_file, document_original_name, link_url FROM desk_evaluation_documents WHERE desk_evaluation_item_id = ?");

        $stmtDoc->bind_param("i", $item['id']);

        $this->execute($stmtDoc);

        $documents = $this->fetchAll($stmtDoc);

        return [
            'notes' => $item['notes'] ?? '',
            'documents' => $documents,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Checklist by Assignment
    |--------------------------------------------------------------------------
    */

    public function getByAssignment(int $assignmentId): array
    {
        $stmt = $this->prepare("
            SELECT
                c.id, c.assignment_id, c.standard_id, c.audit_indicator_id,
                c.order_number, c.status, c.progress_percent,
                s.code AS standard_code, s.name AS standard_name,
                ai.item_code, ai.statement, ai.indicator, ai.target, ai.indicator_type,
                r.audit_status, r.achievement, r.document_audit_result, r.field_audit_result,
                r.finding, r.root_cause, r.supporting_factor,
                r.recommendation, r.evidence, r.notes, r.audited_at
            FROM audit_checklists c
            LEFT JOIN standards s ON s.id = c.standard_id
            LEFT JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            LEFT JOIN audit_checklist_results r ON r.checklist_id = c.id
            WHERE c.assignment_id = ?
            ORDER BY c.order_number ASC
        ");

        $stmt->bind_param("i", $assignmentId);

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Checklist By Id
    |--------------------------------------------------------------------------
    */

    public function findChecklistById(int $id): ?array
    {
        $stmt = $this->prepare("
            SELECT
                c.*,
                s.code AS standard_code, s.name AS standard_name,
                ai.item_code, ai.statement, ai.indicator, ai.target, ai.indicator_type,
                r.id AS result_id, r.audit_status, r.achievement,
                r.document_audit_result, r.field_audit_result,
                r.finding, r.root_cause, r.supporting_factor,
                r.recommendation, r.evidence, r.notes,
                r.is_temuan_risiko_tinggi, r.rekomendasi_mitigasi_segera
            FROM audit_checklists c
            LEFT JOIN standards s ON s.id = c.standard_id
            LEFT JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            LEFT JOIN audit_checklist_results r ON r.checklist_id = c.id
            WHERE c.id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $id);

        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Save Result (insert atau update)
    |--------------------------------------------------------------------------
    */

    public function getContextForRisk(int $checklistId): ?array
    {
        $stmt = $this->prepare("
            SELECT a.auditee_id AS unit_id, ai.item_code, ai.indicator, s.name AS standard_name
            FROM audit_checklists c
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $checklistId);
        $this->execute($stmt);

        return $this->fetchOne($stmt);
    }

    public function saveResult(int $checklistId, array $data): void
    {
        $existing = $this->prepare("SELECT id FROM audit_checklist_results WHERE checklist_id = ? LIMIT 1");
        $existing->bind_param("i", $checklistId);
        $this->execute($existing);
        $row = $this->fetchOne($existing);

        if ($row) {

            $stmt = $this->prepare("
                UPDATE audit_checklist_results
                SET
                    auditor_id = ?, achievement = ?, document_audit_result = ?, field_audit_result = ?,
                    audit_status = ?, root_cause = ?, supporting_factor = ?,
                    recommendation = ?, evidence = ?, notes = ?,
                    is_temuan_risiko_tinggi = ?, rekomendasi_mitigasi_segera = ?,
                    audited_at = NOW(), updated_at = NOW()
                WHERE checklist_id = ?
            ");

            $stmt->bind_param(
                "isssssssssisi",
                $data['auditor_id'],
                $data['achievement'],
                $data['document_audit_result'],
                $data['field_audit_result'],
                $data['audit_status'],
                $data['root_cause'],
                $data['supporting_factor'],
                $data['recommendation'],
                $data['evidence'],
                $data['notes'],
                $data['is_temuan_risiko_tinggi'],
                $data['rekomendasi_mitigasi_segera'],
                $checklistId
            );

        } else {

            $stmt = $this->prepare("
                INSERT INTO audit_checklist_results
                    (checklist_id, auditor_id, achievement, document_audit_result, field_audit_result,
                     audit_status, root_cause, supporting_factor, recommendation,
                     evidence, notes, is_temuan_risiko_tinggi, rekomendasi_mitigasi_segera, audited_at, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->bind_param(
                "iissssssssiss",
                $checklistId,
                $data['auditor_id'],
                $data['achievement'],
                $data['document_audit_result'],
                $data['field_audit_result'],
                $data['audit_status'],
                $data['root_cause'],
                $data['supporting_factor'],
                $data['recommendation'],
                $data['evidence'],
                $data['notes'],
                $data['is_temuan_risiko_tinggi'],
                $data['rekomendasi_mitigasi_segera']
            );
        }

        $this->execute($stmt);

        $this->updateChecklistStatus($checklistId);
    }

    /*
    |--------------------------------------------------------------------------
    | Update status & progress checklist (setelah hasil disimpan)
    |--------------------------------------------------------------------------
    */

    private function updateChecklistStatus(int $checklistId): void
    {
        $stmt = $this->prepare("
            UPDATE audit_checklists
            SET status = 'Selesai', progress_percent = 100, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("i", $checklistId);

        $this->execute($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | Progress Summary
    |--------------------------------------------------------------------------
    */

    public function getProgress(int $assignmentId): array
    {
        $stmt = $this->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) AS selesai
            FROM audit_checklists
            WHERE assignment_id = ?
        ");

        $stmt->bind_param("i", $assignmentId);

        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        $total = (int)($row['total'] ?? 0);
        $selesai = (int)($row['selesai'] ?? 0);
        $percent = $total > 0 ? round(($selesai / $total) * 100) : 0;

        return [
            'total' => $total,
            'selesai' => $selesai,
            'belum' => $total - $selesai,
            'percent' => $percent,
        ];
    }
}