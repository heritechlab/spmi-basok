<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class RisikoRepository extends BaseRepository
{
    protected string $table = 'risk_register';

    public function getQualityManualsGrouped(int $unitId = 0): array
    {
        $stmt = $this->prepare("
            SELECT id, title AS name, category, 'manual_mutu' AS sumber_tabel
            FROM quality_manuals
            UNION ALL
            SELECT id, title AS name, category, 'kebijakan_mutu' AS sumber_tabel
            FROM quality_policies
            ORDER BY category ASC, name ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getIndicators(int $unitId = 0): array
    {
        $sql = "
            SELECT ai.id, ai.item_code, ai.indicator, ai.statement, s.name AS standard_name
            FROM audit_indicators ai
            JOIN standards s ON s.id = ai.standard_id
            WHERE ai.status = 1
        ";

        if ($unitId > 0) {
            $sql .= "
                AND (
                    NOT EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id)
                    OR EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id AND aiu.unit_id = ?)
                )
            ";
        }

        $sql .= " ORDER BY s.name ASC, ai.sort_order ASC ";

        $stmt = $this->prepare($sql);

        if ($unitId > 0) {
            $stmt->bind_param("i", $unitId);
        }

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getStandardStatements(int $unitId = 0): array
    {
        $sql = "
            SELECT ss.id, ss.statement_text, ss.owner_type, ss.owner_unit_id, s.name AS standard_name, s.code AS standard_code
            FROM standard_statements ss
            JOIN standards s ON s.id = ss.standard_id
        ";

        $isProdi = false;

        if ($unitId > 0) {
            $stmtUnit = $this->prepare("SELECT type FROM units WHERE id = ? LIMIT 1");
            $stmtUnit->bind_param("i", $unitId);
            $this->execute($stmtUnit);
            $unitRow = $this->fetchOne($stmtUnit);
            $isProdi = ($unitRow['type'] ?? '') === 'Program Studi';
        }

        if ($unitId > 0) {
            if ($isProdi) {
                $sql .= " WHERE (ss.owner_type IS NULL OR ss.owner_type = 'prodi') ";
            } else {
                $sql .= " WHERE ss.owner_type = 'unit' AND ss.owner_unit_id = ? ";
            }
        }

        $sql .= " ORDER BY s.name ASC, ss.sort_order ASC ";

        $stmt = $this->prepare($sql);

        if ($unitId > 0 && !$isProdi) {
            $stmt->bind_param("i", $unitId);
        }

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getRiskCountBySumber(string $sumberJenis, int $sumberId): int
    {
        $stmt = $this->prepare("SELECT COUNT(*) AS total FROM risk_register WHERE sumber_jenis = ? AND sumber_id = ?");
        $stmt->bind_param("si", $sumberJenis, $sumberId);
        $this->execute($stmt);

        return (int) ($this->fetchOne($stmt)['total'] ?? 0);
    }

    public function getAllRiskCounts(): array
    {
        $stmt = $this->prepare("SELECT sumber_jenis, sumber_id, COUNT(*) AS total FROM risk_register GROUP BY sumber_jenis, sumber_id");
        $this->execute($stmt);

        $map = [];
        foreach ($this->fetchAll($stmt) as $row) {
            $map[$row['sumber_jenis'] . '_' . $row['sumber_id']] = (int) $row['total'];
        }

        return $map;
    }

    public function createRisk(int $unitId, string $sumberJenis, int $sumberId, string $deskripsi, ?string $kategori, ?int $createdBy): int
    {
        $stmt = $this->prepare("
            INSERT INTO risk_register (unit_id, sumber_jenis, sumber_id, deskripsi_risiko, kategori_risiko, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isissi", $unitId, $sumberJenis, $sumberId, $deskripsi, $kategori, $createdBy);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function getRisksByStatus(array $statuses): array
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $types = str_repeat('s', count($statuses));

        $stmt = $this->prepare("
            SELECT r.*, u.name AS unit_name
            FROM risk_register r
            LEFT JOIN units u ON u.id = r.unit_id
            WHERE r.status IN ($placeholders)
            ORDER BY FIELD(r.level_risiko, 'Ekstrem','Tinggi','Sedang','Rendah') ASC, r.created_at DESC
        ");
        $stmt->bind_param($types, ...$statuses);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function updateAnalysis(int $riskId, int $likelihood, int $impact, ?int $detectability, ?int $controlReadiness, string $levelRisiko, ?string $deskripsiDampak): void
    {
        $stmt = $this->prepare("
            UPDATE risk_register
            SET likelihood = ?, impact = ?, detectability = ?, control_readiness = ?, level_risiko = ?, deskripsi_dampak = ?, status = 'Teranalisis'
            WHERE id = ?
        ");
        $stmt->bind_param("iiiissi", $likelihood, $impact, $detectability, $controlReadiness, $levelRisiko, $deskripsiDampak, $riskId);
        $this->execute($stmt);
    }

    public function updateStatus(int $riskId, string $status): void
    {
        $stmt = $this->prepare("UPDATE risk_register SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $riskId);
        $this->execute($stmt);
    }

    public function findRiskById(int $id): ?array
    {
        $stmt = $this->prepare("SELECT * FROM risk_register WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt);
    }

    public function getMitigationPlans(int $riskId): array
    {
        $stmt = $this->prepare("
            SELECT mp.*, u.full_name AS pic_name
            FROM risk_mitigation_plan mp
            LEFT JOIN users u ON u.id = mp.pic_user_id
            WHERE mp.risk_id = ?
            ORDER BY mp.created_at ASC
        ");
        $stmt->bind_param("i", $riskId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createMitigationPlan(int $riskId, string $tindakan, ?string $akarMasalah, ?string $indikatorKeberhasilan, ?int $picUserId, ?string $targetTanggal, int $deadlineHari): int
    {
        $stmt = $this->prepare("
            INSERT INTO risk_mitigation_plan (risk_id, tindakan_mitigasi, akar_masalah, indikator_keberhasilan, pic_user_id, target_tanggal, deadline_hari)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssisi", $riskId, $tindakan, $akarMasalah, $indikatorKeberhasilan, $picUserId, $targetTanggal, $deadlineHari);
        $this->execute($stmt);

        return $this->insertId();
    }

    public function updateStatusEfektivitas(int $planId, string $status): void
    {
        $stmt = $this->prepare("UPDATE risk_mitigation_plan SET status_efektivitas = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $planId);
        $this->execute($stmt);
    }

    public function updateMitigationStatus(int $planId, string $status): void
    {
        $stmt = $this->prepare("UPDATE risk_mitigation_plan SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $planId);
        $this->execute($stmt);
    }

    public function getAccCriteria(): array
    {
        $stmt = $this->prepare("SELECT id, name FROM acc_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$r) {
            $r['name'] = preg_replace('/^Kriteria\s*\d+\.\s*/i', '', $r['name']);
        }
        unset($r);

        return $rows;
    }

    public function createRiskFromFinding(int $unitId, int $checklistId, string $deskripsi): int
    {
        $stmt = $this->prepare("
            INSERT INTO risk_register (unit_id, sumber_jenis, sumber_id, deskripsi_risiko, kategori_risiko, status)
            VALUES (?, 'temuan_audit', ?, ?, 'Kepatuhan', 'Teridentifikasi')
        ");
        $stmt->bind_param("iis", $unitId, $checklistId, $deskripsi);
        $this->execute($stmt);

        return $this->insertId();
    }
    public function getPrioritasAuditPerUnit(): array
    {
        $stmt = $this->prepare("
            SELECT
                u.id AS unit_id, u.code, u.name,
                SUM(CASE WHEN r.level_risiko = 'Ekstrem' THEN 1 ELSE 0 END) AS jumlah_ekstrem,
                SUM(CASE WHEN r.level_risiko = 'Tinggi' THEN 1 ELSE 0 END) AS jumlah_tinggi,
                SUM(CASE WHEN r.level_risiko = 'Sedang' THEN 1 ELSE 0 END) AS jumlah_sedang,
                SUM(CASE WHEN r.level_risiko = 'Rendah' THEN 1 ELSE 0 END) AS jumlah_rendah,
                COUNT(r.id) AS total_risiko
            FROM units u
            LEFT JOIN risk_register r ON r.unit_id = u.id AND r.status IN ('Teranalisis','Termitigasi')
            GROUP BY u.id, u.code, u.name
            ORDER BY jumlah_ekstrem DESC, jumlah_tinggi DESC, u.name ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    public function getFinishedAssignments(): array
    {
        $stmt = $this->prepare("
            SELECT a.id, a.assignment_number, a.audit_code, a.completion_date,
                   u.name AS unit_name, p.period_name,
                   a.pascaaudit_disampaikan
            FROM audit_assignments a
            JOIN units u ON u.id = a.auditee_id
            LEFT JOIN audit_periods p ON p.id = a.period_id
            WHERE a.status = 'Selesai'
            ORDER BY a.pascaaudit_disampaikan ASC, a.completion_date DESC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getRisksByAssignment(int $assignmentId): array
    {
        $stmt = $this->prepare("
            SELECT r.*, ai.item_code, ai.indicator, s.name AS standard_name
            FROM risk_register r
            JOIN audit_checklists c ON c.id = r.sumber_id AND r.sumber_jenis = 'temuan_audit'
            JOIN audit_indicators ai ON ai.id = c.audit_indicator_id
            JOIN standards s ON s.id = c.standard_id
            WHERE c.assignment_id = ?
            ORDER BY FIELD(r.level_risiko, 'Ekstrem','Tinggi','Sedang','Rendah') ASC
        ");
        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function updatePascaauditReview(int $riskId, int $likelihood, int $impact, ?int $detectability, ?int $controlReadiness, string $levelRisiko, string $rekomendasi, int $reviewedBy): void
    {
        $stmt = $this->prepare("
            UPDATE risk_register
            SET likelihood = ?, impact = ?, detectability = ?, control_readiness = ?,
                level_risiko = ?, rekomendasi_lanjutan = ?,
                reviewed_pascaaudit = 1, reviewed_by = ?, reviewed_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("iiiissii", $likelihood, $impact, $detectability, $controlReadiness, $levelRisiko, $rekomendasi, $reviewedBy, $riskId);
        $this->execute($stmt);
    }

    public function markAssignmentDisampaikan(int $assignmentId): void
    {
        $stmt = $this->prepare("UPDATE audit_assignments SET pascaaudit_disampaikan = 1, pascaaudit_disampaikan_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $assignmentId);
        $this->execute($stmt);
    }

    public function getRisksBySumberList(array $sumberPairs): array
    {
        $risks = [];

        foreach ($sumberPairs as $pair) {
            if ($pair['sumber_id'] <= 0) continue;

            $stmt = $this->prepare("
                SELECT deskripsi_risiko, kategori_risiko, level_risiko, status
                FROM risk_register
                WHERE sumber_jenis = ? AND sumber_id = ?
                ORDER BY FIELD(level_risiko,'Ekstrem','Tinggi','Sedang','Rendah') ASC
            ");
            $stmt->bind_param("si", $pair['sumber_jenis'], $pair['sumber_id']);
            $this->execute($stmt);

            foreach ($this->fetchAll($stmt) as $r) {
                $risks[] = $r;
            }
        }

        return $risks;
    }

    public function getRtlOptionsForRisk(int $riskId): array
    {
        $risk = $this->findRiskById($riskId);
        if (!$risk || $risk['sumber_jenis'] !== 'temuan_audit') return [];

        $stmt = $this->prepare("
            SELECT rap.id, rap.activity, rap.status, rap.pic
            FROM rtm_action_plans rap
            JOIN audit_checklist_results r ON r.id = rap.checklist_result_id
            WHERE r.checklist_id = ?
              AND rap.id NOT IN (SELECT rtl_id FROM risk_rtl_link WHERE risk_id = ?)
            ORDER BY rap.created_at DESC
        ");
        $stmt->bind_param("ii", $risk['sumber_id'], $riskId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getPtpOptionsForRisk(int $riskId): array
    {
        $risk = $this->findRiskById($riskId);
        if (!$risk || $risk['sumber_jenis'] !== 'temuan_audit') return [];

        $stmt = $this->prepare("
            SELECT p.id, p.description, p.status, p.new_indicator
            FROM ptp_items p
            JOIN audit_checklist_results r ON r.id = p.checklist_result_id
            WHERE r.checklist_id = ?
              AND p.id NOT IN (SELECT ptp_id FROM risk_ptp_link WHERE risk_id = ?)
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("ii", $risk['sumber_id'], $riskId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getLinkedRtl(int $riskId): array
    {
        $stmt = $this->prepare("
            SELECT rap.id, rap.activity, rap.status, rap.pic
            FROM risk_rtl_link l
            JOIN rtm_action_plans rap ON rap.id = l.rtl_id
            WHERE l.risk_id = ?
        ");
        $stmt->bind_param("i", $riskId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getLinkedPtp(int $riskId): array
    {
        $stmt = $this->prepare("
            SELECT p.id, p.description, p.status
            FROM risk_ptp_link l
            JOIN ptp_items p ON p.id = l.ptp_id
            WHERE l.risk_id = ?
        ");
        $stmt->bind_param("i", $riskId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function linkToRtl(int $riskId, int $rtlId): void
    {
        $stmt = $this->prepare("INSERT IGNORE INTO risk_rtl_link (risk_id, rtl_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $riskId, $rtlId);
        $this->execute($stmt);
    }

    public function linkToPtp(int $riskId, int $ptpId): void
    {
        $stmt = $this->prepare("INSERT IGNORE INTO risk_ptp_link (risk_id, ptp_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $riskId, $ptpId);
        $this->execute($stmt);
    }

    public function getUnitList(): array
    {
        $stmt = $this->prepare("SELECT id, code, name FROM units ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getUserList(): array
    {
        $stmt = $this->prepare("SELECT id, full_name FROM users ORDER BY full_name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}