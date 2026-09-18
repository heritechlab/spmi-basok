<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class CapaianKriteriaRepository extends BaseRepository
{
    protected string $table = 'standards';
    private mysqli $dbConn;

    public function __construct(mysqli $conn)
    {
        parent::__construct($conn);
        $this->dbConn = $conn;
    }

    public function getProdiCriteria(): array
    {
        $stmt = $this->prepare("SELECT id, name FROM acc_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getInstitutionCriteria(): array
    {
        $stmt = $this->prepare("SELECT id, parent_id, name FROM institution_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getStandardsFlat(): array
    {
        $stmt = $this->prepare("
            SELECT id, code, name, prodi_criteria_id, institution_criteria_id
            FROM standards
            WHERE is_active = 1
            ORDER BY code ASC
        ");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveStandardMapping(int $standardId, ?int $prodiCriteriaId, ?int $institutionCriteriaId): void
    {
        $stmt = $this->prepare("UPDATE standards SET prodi_criteria_id = ?, institution_criteria_id = ? WHERE id = ?");
        $stmt->bind_param("iii", $prodiCriteriaId, $institutionCriteriaId, $standardId);
        $this->execute($stmt);
    }

        public function getStandardAchievementByUnitWithFallback(int $unitId, int $periodId): array
    {
        // Langkah 1: ambil hasil yg MEMANG dinilai khusus utk Prodi ini
        $prodiResults = $this->getStandardAchievementByUnit($unitId, $periodId);

        $resultByStandardId = [];
        foreach ($prodiResults as $row) {
            $resultByStandardId[(int) $row['standard_id']] = $row;
        }

        // Langkah 2: ambil hasil dari Unit NON-PRODI (BAUK, dll) di Periode yg sama,
        // sbg cadangan utk Standar yg TIDAK ADA hasil khusus Prodi-nya
        $stmt = $this->prepare("
            SELECT
                s.id AS standard_id, s.code AS standard_code, s.name AS standard_name,
                COUNT(*) AS total,
                SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN units u ON u.id = a.auditee_id
            JOIN standards s ON s.id = c.standard_id
            WHERE a.period_id = ?
              AND u.type <> 'Program Studi'
              AND r.audit_status IS NOT NULL AND r.audit_status <> ''
            GROUP BY s.id, s.code, s.name
        ");
        $stmt->bind_param("i", $periodId);
        $this->execute($stmt);

        $sharedRows = $this->fetchAll($stmt);

        foreach ($sharedRows as $row) {

            $standardId = (int) $row['standard_id'];

            // Kalau Standar ini SUDAH ada hasil khusus utk Prodi, JANGAN ditimpa
            if (isset($resultByStandardId[$standardId])) {
                continue;
            }

            $total = (int) $row['total'];

            $skor = $total > 0
                ? round((
                    (int) $row['tidak_terpenuhi'] * 1
                    + (int) $row['sebagian'] * 2
                    + (int) $row['memenuhi'] * 3
                    + (int) $row['melampaui'] * 4
                ) / $total, 2)
                : null;

            $row['skor_capaian'] = $skor;
            $row['is_shared'] = true;

            $resultByStandardId[$standardId] = $row;
        }

        return array_values($resultByStandardId);
    }

    public function getStandardAchievementByUnit(int $unitId, int $periodId): array
    {
        require_once __DIR__ . '/../laporan/ami/repository.php';

        $amiRepo = new AmiReportRepository($this->dbConn);

        return $amiRepo->getStandardAchievementSummary($unitId, $periodId);
    }

    public function getStandardAchievementInstitution(int $periodId): array
    {
        $stmt = $this->prepare("
            SELECT
                s.id AS standard_id, s.code AS standard_code, s.name AS standard_name,
                COUNT(*) AS total,
                SUM(CASE WHEN r.audit_status = 'Menyimpang' THEN 1 ELSE 0 END) AS tidak_terpenuhi,
                SUM(CASE WHEN r.audit_status = 'Belum Mencapai' THEN 1 ELSE 0 END) AS sebagian,
                SUM(CASE WHEN r.audit_status = 'Mencapai' THEN 1 ELSE 0 END) AS memenuhi,
                SUM(CASE WHEN r.audit_status = 'Melampaui' THEN 1 ELSE 0 END) AS melampaui
            FROM audit_checklist_results r
            JOIN audit_checklists c ON c.id = r.checklist_id
            JOIN audit_assignments a ON a.id = c.assignment_id
            JOIN standards s ON s.id = c.standard_id
            WHERE a.period_id = ?
              AND r.audit_status IS NOT NULL AND r.audit_status <> ''
            GROUP BY s.id, s.code, s.name
            ORDER BY s.code ASC
        ");
        $stmt->bind_param("i", $periodId);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        foreach ($rows as &$row) {

            $total = (int) $row['total'];

            $skor = $total > 0
                ? round((
                    (int) $row['tidak_terpenuhi'] * 1
                    + (int) $row['sebagian'] * 2
                    + (int) $row['memenuhi'] * 3
                    + (int) $row['melampaui'] * 4
                ) / $total, 2)
                : null;

            $row['skor_capaian'] = $skor;
        }

        return $rows;
    }

    public function getPeriods(): array
    {
        $stmt = $this->prepare("SELECT id, period_name FROM audit_periods ORDER BY id DESC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getProdiUnits(): array
    {
        $stmt = $this->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
}