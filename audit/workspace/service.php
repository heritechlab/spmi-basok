<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class WorkspaceService extends BaseService
{
    public function __construct(WorkspaceRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): WorkspaceRepository
    {
        /** @var WorkspaceRepository */
        return parent::repository();
    }

    public function getChecklist(int $assignmentId): array
    {
        $this->repository()->generateChecklist($assignmentId);

        return $this->success(
            $this->repository()->getByAssignment($assignmentId)
        );
    }

    public function getProgress(int $assignmentId): array
    {
        return $this->success(
            $this->repository()->getProgress($assignmentId)
        );
    }

    public function getChecklistItem(int $id): array
    {
        $data = $this->repository()->findChecklistById($id);

        if (!$data) {
            return $this->error('Item checklist tidak ditemukan.');
        }

        return $this->success($data);
    }

private function validate(array $data): void
    {
        if (!in_array($data['audit_status'], ['Menyimpang', 'Belum Mencapai', 'Mencapai', 'Melampaui'], true)) {
            throw new InvalidArgumentException('Status capaian wajib dipilih.');
        }
    }

    public function saveResult(int $checklistId, int $assignmentId, array $input): array
    {
        try {

            $data = [
                'auditor_id'             => (int)($input['auditor_id'] ?? 0),
                'achievement'            => trim($input['achievement'] ?? ''),
                'document_audit_result'  => trim($input['document_audit_result'] ?? ''),
                'field_audit_result'     => trim($input['field_audit_result'] ?? ''),
                'audit_status'           => trim($input['audit_status'] ?? ''),
                'root_cause'             => trim($input['root_cause'] ?? ''),
                'supporting_factor'      => trim($input['supporting_factor'] ?? ''),
                'recommendation'         => trim($input['recommendation'] ?? ''),
                'evidence'               => trim($input['evidence'] ?? ''),
                'notes'                  => trim($input['notes'] ?? ''),
                'is_temuan_risiko_tinggi' => !empty($input['is_temuan_risiko_tinggi']) ? 1 : 0,
                'rekomendasi_mitigasi_segera' => trim($input['rekomendasi_mitigasi_segera'] ?? ''),
            ];

            $this->validate($data);

            $this->repository()->saveResult($checklistId, $data);

            $this->notifyIfLkaComplete($assignmentId);

            return $this->success(null, 'Hasil audit berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    private function notifyIfLkaComplete(int $assignmentId): void
    {
        try {

            if (!$this->repository()->isAllChecklistFilled($assignmentId)) {
                return;
            }

            $info = $this->repository()->getAssignmentNotificationInfo($assignmentId);

            if (!$info || (int) $info['lka_notified'] === 1 || empty($info['unit_email'])) {
                return;
            }

            require_once __DIR__ . '/../../core/Mailer.php';

            $mailer = new Mailer();

            $body = "
                <p>Yth. " . htmlspecialchars($info['head_name'] ?: $info['unit_name']) . ",</p>
                <p>Proses Audit Mutu Internal (Audit Dokumen & Audit Lapangan) untuk penugasan <strong>" . htmlspecialchars($info['assignment_number']) . "</strong> pada unit Anda telah selesai dilaksanakan oleh Auditor.</p>
                <p>Silakan login ke SIQUA untuk melihat hasil Temuan Audit pada unit Anda.</p>
                <p>Terima kasih.</p>
            ";

            $mailer->send($info['unit_email'], $info['head_name'] ?: $info['unit_name'], 'Audit Mutu Internal Selesai - ' . $info['assignment_number'], $body);

            require_once __DIR__ . '/../../core/Notifier.php';

            $stmtUnitId = $this->repository()->getConnection()->prepare("SELECT auditee_id FROM audit_assignments WHERE id = ? LIMIT 1");
            $stmtUnitId->bind_param("i", $assignmentId);
            $stmtUnitId->execute();
            $unitIdRow = $stmtUnitId->get_result()->fetch_assoc();

            Notifier::sendToUnit(
                $this->repository()->getConnection(),
                (int) ($unitIdRow['auditee_id'] ?? 0),
                'Audit Mutu Internal Selesai',
                'Hasil audit untuk penugasan ' . $info['assignment_number'] . ' sudah tersedia di Temuan Audit.',
                BASE_URL . 'audit/findings/'
            );

            $this->repository()->markLkaNotified($assignmentId);

        } catch (Throwable $e) {
            error_log('Gagal kirim email notifikasi LKA selesai: ' . $e->getMessage());
        }
    }
    public function getDeskEvaluation(int $assignmentId, int $standardId, int $indicatorId): array
    {
        $data = $this->repository()->getDeskEvaluation($assignmentId, $standardId, $indicatorId);

        return $this->success($data);
    }
}