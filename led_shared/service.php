<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class LedService extends BaseService
{
    public function __construct(LedRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): LedRepository
    {
        /** @var LedRepository */
        return parent::repository();
    }

    public function getMyUnitId(int $userId): int
    {
        return $this->repository()->getMyUnitId($userId);
    }

    public function getMyAssignments(int $unitId): array
    {
        return $this->success($this->repository()->getMyAssignments($unitId));
    }

    public function getAssignment(int $assignmentId): ?array
    {
        return $this->repository()->getAssignment($assignmentId);
    }

    public function getLedStructure(string $level, int $assignmentId): array
    {
        $kriteriaList = $this->repository()->getKriteria($level);
        $allowedStandardIds = $this->repository()->getAssignedStandardIds($assignmentId);

        $assignment = $this->repository()->getAssignmentAuditeeType($assignmentId);
        $isProdi = ($assignment['type'] ?? '') === 'Program Studi';
        $auditeeUnitId = (int) ($assignment['auditee_id'] ?? 0);

        $topLevel = $level === 'prodi'
            ? $kriteriaList
            : array_filter($kriteriaList, fn($k) => $k['parent_id'] === null);

        $result = [];

        foreach ($topLevel as $kriteria) {

            $criteriaIdsToCheck = [$kriteria['id']];

            if ($level === 'institusi') {
                $children = array_filter($kriteriaList, fn($k) => (int) ($k['parent_id'] ?? 0) === (int) $kriteria['id']);
                if (!empty($children)) {
                    $criteriaIdsToCheck = array_column($children, 'id');
                }
            }

            $standardsBlock = [];

            foreach ($criteriaIdsToCheck as $critId) {

                $standards = $this->repository()->getStandardsByKriteria($level, $critId, $allowedStandardIds);

                foreach ($standards as $std) {

                    $indicators = $this->repository()->getIndicatorsByStandard($std['id'], $isProdi, $auditeeUnitId);
                    $indicatorIds = array_column($indicators, 'id');

                    $entries = $this->repository()->getEntriesByAssignment($indicatorIds, $assignmentId);

                    foreach ($indicators as &$ind) {
                        $ind['entry'] = $entries[$ind['id']] ?? null;
                    }

                    $standardsBlock[] = [
                        'standard_id'   => $std['id'],
                        'standard_code' => $std['code'],
                        'standard_name' => $std['name'],
                        'indicators'    => $indicators,
                    ];
                }
            }

            if (!empty($standardsBlock)) {
                $result[] = [
                    'criteria_id'   => $kriteria['id'],
                    'criteria_name' => $kriteria['name'],
                    'standards'     => $standardsBlock,
                ];
            }
        }

        return $this->success($result);
    }

    public function saveEntry(array $input, array $files, int $updatedBy, int $myUnitId): array
    {
        try {

            $assignmentId = (int) ($input['assignment_id'] ?? 0);
            $standardId = (int) ($input['standard_id'] ?? 0);
            $indicatorId = (int) ($input['indicator_id'] ?? 0);

            $assignment = $this->repository()->getAssignment($assignmentId);

            if (!$assignment || (int) $assignment['auditee_id'] !== $myUnitId) {
                throw new InvalidArgumentException('Akses ditolak. Penugasan ini bukan milik unit Anda.');
            }

            if ($indicatorId <= 0) {
                throw new InvalidArgumentException('Indikator tidak valid.');
            }

            $itemId = $this->repository()->saveItem(
                $assignmentId,
                $standardId,
                $indicatorId,
                trim($input['pernyataan_evaluasi'] ?? ''),
                trim($input['capaian_realisasi'] ?? '')
            );

            $linkUrl = trim($input['dokumen_link'] ?? '');

            if ($linkUrl !== '' && filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                $this->repository()->saveLink($itemId, $linkUrl);
            }

            if (!empty($files['name'])) {

                require_once __DIR__ . '/../core/UploadHelper.php';

                $uploader = new UploadHelper(
                    dirname(__DIR__) . '/uploads/desk_evaluation',
                    ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
                    [
                        'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg', 'image/png',
                    ]
                );

                $uploaded = $uploader->upload($files);

                $this->repository()->saveDocument(
                    $itemId,
                    $uploaded['document_file'],
                    $uploaded['document_original_name'],
                    (int) ($uploaded['document_size'] ?? 0)
                );
            }

            $this->refreshEvaluationStatus($assignmentId, $standardId);
            $this->notifyIfComplete($assignmentId);

            return $this->success(null, 'Isian LED berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    private function refreshEvaluationStatus(int $assignmentId, int $standardId): void
    {
        $evalId = $this->repository()->getOrCreateEvaluation($assignmentId, $standardId);

        $indicators = $this->repository()->getIndicatorsByStandard($standardId);
        $indicatorIds = array_column($indicators, 'id');
        $entries = $this->repository()->getEntriesByAssignment($indicatorIds, $assignmentId);

        $allFilled = !empty($indicatorIds);

        foreach ($indicatorIds as $indId) {
            $entry = $entries[$indId] ?? null;
            if (empty($entry['notes'])) {
                $allFilled = false;
                break;
            }
        }

        $this->repository()->updateEvaluationStatus($evalId, $allFilled ? 'Selesai' : 'Belum');
    }

    private function notifyIfComplete(int $assignmentId): void
    {
        try {

            global $conn;

            $stmtIds = $conn->prepare("SELECT standard_id FROM assignment_standards WHERE assignment_id = ?");
            $stmtIds->bind_param("i", $assignmentId);
            $stmtIds->execute();
            $selectedStandardIds = array_column($stmtIds->get_result()->fetch_all(MYSQLI_ASSOC), 'standard_id');

            if (!empty($selectedStandardIds)) {
                $totalStandards = count($selectedStandardIds);
            } else {
                $countAll = $conn->query("SELECT COUNT(*) AS total FROM standards WHERE status = 1")->fetch_assoc();
                $totalStandards = (int) ($countAll['total'] ?? 0);
            }

            if ($totalStandards <= 0) {
                return;
            }

            $stmtDone = $conn->prepare("SELECT COUNT(*) AS done FROM desk_evaluations WHERE assignment_id = ? AND status = 'Selesai'");
            $stmtDone->bind_param("i", $assignmentId);
            $stmtDone->execute();
            $totalDone = (int) ($stmtDone->get_result()->fetch_assoc()['done'] ?? 0);

            if ($totalDone < $totalStandards) {
                return;
            }

            $stmtAssignment = $conn->prepare("
                SELECT a.assignment_number, a.desk_eval_notified, us.full_name AS lead_name, us.email AS lead_email, u.name AS unit_name
                FROM audit_assignments a
                LEFT JOIN users us ON us.id = a.lead_auditor
                LEFT JOIN units u ON u.id = a.auditee_id
                WHERE a.id = ?
                LIMIT 1
            ");
            $stmtAssignment->bind_param("i", $assignmentId);
            $stmtAssignment->execute();
            $assignment = $stmtAssignment->get_result()->fetch_assoc();

            if (!$assignment || (int) $assignment['desk_eval_notified'] === 1 || empty($assignment['lead_email'])) {
                return;
            }

            require_once __DIR__ . '/../core/Mailer.php';

            $mailer = new Mailer();

            $body = "
                <p>Yth. " . htmlspecialchars($assignment['lead_name'] ?? 'Bapak/Ibu') . ",</p>
                <p>LED (Laporan Evaluasi Diri) untuk penugasan audit <strong>" . htmlspecialchars($assignment['assignment_number']) . "</strong> pada unit <strong>" . htmlspecialchars($assignment['unit_name'] ?? '-') . "</strong> telah selesai diisi oleh Auditee untuk seluruh Standar.</p>
                <p>Silakan login ke SIQUA untuk melanjutkan proses Audit Dokumen dan Audit Lapangan (Workspace Audit/LKA).</p>
                <p>Terima kasih.</p>
            ";

            $mailer->send($assignment['lead_email'], $assignment['lead_name'] ?? '', 'LED Selesai - ' . $assignment['assignment_number'], $body);

            $stmtLead = $conn->prepare("SELECT lead_auditor FROM audit_assignments WHERE id = ? LIMIT 1");
            $stmtLead->bind_param("i", $assignmentId);
            $stmtLead->execute();
            $leadRow = $stmtLead->get_result()->fetch_assoc();

            require_once __DIR__ . '/../core/Notifier.php';

            Notifier::send(
                $conn,
                (int) ($leadRow['lead_auditor'] ?? 0),
                'LED Selesai',
                'LED ' . $assignment['assignment_number'] . ' telah selesai diisi Auditee.',
                BASE_URL . 'audit/workspace/'
            );

            $update = $conn->prepare("UPDATE audit_assignments SET desk_eval_notified = 1 WHERE id = ?");
            $update->bind_param("i", $assignmentId);
            $update->execute();

        } catch (Throwable $e) {
            error_log('Gagal kirim email notifikasi LED selesai: ' . $e->getMessage());
        }
    }

    public function deleteDocument(int $docId, int $myUnitId): array
    {
        $deleted = $this->repository()->deleteDocument($docId, $myUnitId);

        if (!$deleted) {
            return $this->error('Akses ditolak.');
        }

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
}