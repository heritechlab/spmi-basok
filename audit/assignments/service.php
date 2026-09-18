<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AssignmentService extends BaseService
{
    public function __construct(AssignmentRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AssignmentRepository
    {
        /** @var AssignmentRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', int $periodId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        return $this->success(
            $this->repository()->getAll($search, $periodId, $status, $limit, $offset)
        );
    }

    public function count(string $search = '', int $periodId = 0, string $status = ''): array
    {
        return $this->success(
            $this->repository()->count($search, $periodId, $status)
        );
    }

    public function getStatistics(): array
    {
        return $this->success(
            $this->repository()->getStatistics()
        );
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data penugasan tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'period_id'         => (int)($data['period_id'] ?? 0),
            'auditee_id'        => (int)($data['auditee_id'] ?? 0),
            'lead_auditor'      => !empty($data['lead_auditor']) ? (int)$data['lead_auditor'] : null,
            'assignment_number' => trim($data['assignment_number'] ?? ''),
            'audit_type'        => trim($data['audit_type'] ?? 'AMI'),
            'audit_date'        => !empty($data['audit_date']) ? $data['audit_date'] : null,
            'status'            => trim($data['status'] ?? 'Draft'),
            'notes'             => trim($data['notes'] ?? ''),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['period_id'] <= 0) {
            throw new InvalidArgumentException('Periode audit wajib dipilih.');
        }

        if ($data['auditee_id'] <= 0) {
            throw new InvalidArgumentException('Unit Kerja (auditee) wajib dipilih.');
        }

        if ($data['assignment_number'] === '') {
            throw new InvalidArgumentException('Nomor penugasan wajib diisi.');
        }

        if (!in_array($data['audit_type'], ['AMI', 'Audit Internal', 'Audit Eksternal', 'Surveilans', 'Monitoring'], true)) {
            throw new InvalidArgumentException('Jenis audit tidak valid.');
        }

        if (!in_array($data['status'], ['Draft', 'Dijadwalkan', 'Berlangsung', 'Selesai'], true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsNumber($data['assignment_number'])) {
                throw new InvalidArgumentException('Nomor penugasan sudah digunakan.');
            }

            $id = $this->repository()->create($data);

            $standardIds = $input['standard_ids'] ?? [];
            $this->repository()->saveStandards($id, is_array($standardIds) ? $standardIds : []);

        $this->saveTeamMembersFromInput($id, $input);

            $this->notifyAuditeeNewAssignment($id, $data);
            $this->notifyAuditorsNewAssignment($id, $data);

            $unit = $this->repository()->getUnitContact((int) $data['auditee_id']);
            $unitName = $unit['name'] ?? '-';

            $this->logActivity(
                $_SESSION['user_id'] ?? 0,
                $id,
                'Penugasan Audit baru dibuat: ' . $data['assignment_number'] . ' (' . $unitName . ')',
                'CREATE'
            );

            return $this->success(['id' => $id], 'Penugasan audit berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    private function notifyAuditeeNewAssignment(int $id, array $data): void
    {
        try {

            $unit = $this->repository()->getUnitContact((int) $data['auditee_id']);

            if (!$unit || empty($unit['email'])) {
                return;
            }

            require_once __DIR__ . '/../../core/Mailer.php';

            $mailer = new Mailer();

            $loginUrl = BASE_URL . 'auth/login.php';

            $body = "
                <p>Yth. " . htmlspecialchars($unit['head_name'] ?: $unit['name']) . ",</p>
                <p>Unit Kerja Anda telah mendapatkan penugasan Audit Mutu Internal dengan nomor <strong>" . htmlspecialchars($data['assignment_number']) . "</strong>.</p>
                <p>Mohon segera login ke SIQUA untuk menyetujui penugasan tersebut sebagai langkah awal proses audit.</p>
                <p>Silakan login melalui: <a href='{$loginUrl}'>{$loginUrl}</a></p>
                <p>Terima kasih.</p>
            ";

            $mailer->send($unit['email'], $unit['head_name'] ?: $unit['name'], 'Permintaan Persetujuan Penugasan Audit - ' . $data['assignment_number'], $body);

            require_once __DIR__ . '/../../core/Notifier.php';

            Notifier::sendToUnit(
                $this->repository()->getConnection(),
                (int) $data['auditee_id'],
                'Penugasan Audit Baru',
                'Penugasan ' . $data['assignment_number'] . ' menunggu persetujuan Anda.',
                BASE_URL . 'audit/assignments/'
            );

        } catch (Throwable $e) {
            error_log('Gagal kirim email notifikasi penugasan: ' . $e->getMessage());
        }
    }

    private function notifyAuditorsNewAssignment(int $id, array $data): void
    {
        try {

            require_once __DIR__ . '/../../core/Notifier.php';

            $conn = $this->repository()->getConnection();

            $auditorIds = [];

            if (!empty($data['lead_auditor'])) {
                $auditorIds[] = (int) $data['lead_auditor'];
            }

            $stmtTeam = $conn->prepare("SELECT user_id FROM audit_team_members WHERE assignment_id = ? AND status = 'Aktif'");
            $stmtTeam->bind_param("i", $id);
            $stmtTeam->execute();
            $teamResult = $stmtTeam->get_result();

            while ($row = $teamResult->fetch_assoc()) {
                $auditorIds[] = (int) $row['user_id'];
            }

            $auditorIds = array_unique(array_filter($auditorIds));

            foreach ($auditorIds as $auditorId) {
                Notifier::send(
                    $conn,
                    $auditorId,
                    'Penugasan Audit Baru',
                    'Anda ditugaskan pada Penugasan ' . $data['assignment_number'] . '.',
                    BASE_URL . 'audit/workspace/'
                );
            }

        } catch (Throwable $e) {
            error_log('Gagal kirim notifikasi penugasan ke Auditor: ' . $e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsNumber($data['assignment_number'], $id)) {
                throw new InvalidArgumentException('Nomor penugasan sudah digunakan.');
            }

            $existingBefore = $this->repository()->findById($id);
            $wasCompleted = ($existingBefore['status'] ?? '') === 'Selesai';

            $this->repository()->update($id, $data);

            $standardIds = $input['standard_ids'] ?? [];
            $this->repository()->saveStandards($id, is_array($standardIds) ? $standardIds : []);

            global $conn;
            require_once __DIR__ . '/../workspace/repository.php';
            $workspaceRepo = new WorkspaceRepository($conn);
            $workspaceRepo->syncChecklistWithStandards($id);

            $this->saveTeamMembersFromInput($id, $input);

            if (!$wasCompleted && $data['status'] === 'Selesai') {

                $unit = $this->repository()->getUnitContact((int) $data['auditee_id']);
                $unitName = $unit['name'] ?? '-';

                $this->logActivity(
                    $_SESSION['user_id'] ?? 0,
                    $id,
                    'Penugasan Audit selesai: ' . $data['assignment_number'] . ' (' . $unitName . ')',
                    'STATUS'
                );
            }

            return $this->success(null, 'Penugasan audit berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function approve(int $id): array
    {
        if ($id <= 0) {
            return $this->error('ID penugasan tidak valid.');
        }

        $approvedBy = $_SESSION['user_id'] ?? 0;

        $this->repository()->approve($id, $approvedBy);

        return $this->success([], 'Penugasan berhasil disetujui.');
    }

    
    public function delete(int $id): array
    {
        if ($id <= 0) {
            return $this->error('ID penugasan tidak valid.');
        }

        $deleted = $this->repository()->delete($id);

        if (!$deleted) {
            return $this->error('Penugasan hanya bisa dihapus selama berstatus Draft.');
        }

        return $this->success([], 'Penugasan audit berhasil dihapus.');
    }

        private function logActivity(int $userId, ?int $assignmentId, string $activity, string $type): void
    {
        try {
            global $conn;

            $module = 'Penugasan Audit';
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $browser = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $conn->prepare("
                INSERT INTO activity_logs (user_id, assignment_id, module, activity, activity_type, ip_address, browser, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iisssss", $userId, $assignmentId, $module, $activity, $type, $ip, $browser);
            $stmt->execute();

        } catch (Throwable $e) {
            error_log('Gagal mencatat aktivitas: ' . $e->getMessage());
        }
    }
    private function saveTeamMembersFromInput(int $assignmentId, array $input): void
    {
        $memberIds = $input['team_member_ids'] ?? [];

        if (!is_array($memberIds)) {
            return;
        }

        $members = [];

        foreach ($memberIds as $userId) {
            $members[] = [
                'user_id'      => (int) $userId,
                'role_in_team' => 'Auditor',
            ];
        }

        $this->repository()->saveTeamMembers($assignmentId, $members);
    }

    public function getTeamMembers(int $assignmentId): array
    {
        return $this->success($this->repository()->getTeamMembers($assignmentId));
    }
}