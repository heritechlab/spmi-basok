<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../core/Auth.php';

class RtmService extends BaseService
{
    public function __construct(RtmRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RtmRepository
    {
        /** @var RtmRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', int $periodId = 0, string $status = '', int $unitId = 0, int $limit = 10, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($search, $periodId, $status, $unitId, $limit, $offset));
    }

    public function count(string $search = '', int $periodId = 0, string $status = '', int $unitId = 0): array
    {
        return $this->success($this->repository()->count($search, $periodId, $status, $unitId));
    }

    public function getStatistics(): array
    {
        return $this->success($this->repository()->getStatistics());
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data rapat RTM tidak ditemukan.');
        }

        $data['documents'] = $this->repository()->getDocuments($id);
        $data['action_plans'] = $this->repository()->getActionPlans($id);

        return $this->success($data);
    }

    public function getAvailableFindings(int $periodId, int $unitId = 0): array
    {
        return $this->success($this->repository()->getAvailableFindings($periodId, $unitId));
    }

    private function normalize(array $data): array
    {
        return [
            'period_id'      => (int)($data['period_id'] ?? 0),
            'unit_id'        => (int)($data['unit_id'] ?? 0),
            'meeting_number' => trim($data['meeting_number'] ?? ''),
            'meeting_date'   => !empty($data['meeting_date']) ? $data['meeting_date'] : null,
            'agenda'         => trim($data['agenda'] ?? ''),
            'minutes'        => trim($data['minutes'] ?? ''),
            'status'         => trim($data['status'] ?? 'Draft'),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['period_id'] <= 0) {
            throw new InvalidArgumentException('Periode audit wajib dipilih.');
        }

        if ($data['meeting_number'] === '') {
            throw new InvalidArgumentException('Nomor BAP/Rapat wajib diisi.');
        }

        if (!in_array($data['status'], ['Draft', 'Selesai'], true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);

            if (Auth::isAuditee()) {
                $data['unit_id'] = (int) ($_SESSION['unit_id'] ?? 0);
            }

            $this->validate($data);

            if ($this->repository()->existsNumber($data['meeting_number'])) {
                throw new InvalidArgumentException('Nomor BAP/Rapat sudah digunakan.');
            }

            $data['created_by'] = $_SESSION['user_id'] ?? 0;

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Rapat RTM berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsNumber($data['meeting_number'], $id)) {
                throw new InvalidArgumentException('Nomor BAP/Rapat sudah digunakan.');
            }

            $this->repository()->update($id, $data);

            return $this->success(null, 'Rapat RTM berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function saveBeritaAcaraSignature(int $meetingId, array $input, array $files): array
    {
        try {

            $existing = $this->repository()->findById($meetingId);

            if (!$existing) {
                throw new InvalidArgumentException('Data Rapat tidak ditemukan.');
            }

            require_once __DIR__ . '/../core/UploadHelper.php';
            $uploadRoot = dirname(__DIR__) . '/uploads/signatures';

            $data = [
                'notulis_nama'     => trim($input['notulis_nama'] ?? ''),
                'notulis_tanggal'  => !empty($input['notulis_tanggal']) ? $input['notulis_tanggal'] : null,
                'pimpinan_nama'    => trim($input['pimpinan_nama'] ?? ''),
                'pimpinan_tanggal' => !empty($input['pimpinan_tanggal']) ? $input['pimpinan_tanggal'] : null,
            ];

            if (!empty($files['notulis_ttd']['name'])) {
                $uploader = new UploadHelper($uploadRoot, ['jpg', 'jpeg', 'png'], ['image/jpeg', 'image/png']);
                $uploaded = $uploader->upload($files['notulis_ttd']);
                $data['notulis_ttd'] = $uploaded['document_file'];
            } else {
                $data['notulis_ttd'] = $existing['notulis_ttd'] ?? null;
            }

            if (!empty($files['pimpinan_ttd']['name'])) {
                $uploader = new UploadHelper($uploadRoot, ['jpg', 'jpeg', 'png'], ['image/jpeg', 'image/png']);
                $uploaded = $uploader->upload($files['pimpinan_ttd']);
                $data['pimpinan_ttd'] = $uploaded['document_file'];
            } else {
                $data['pimpinan_ttd'] = $existing['pimpinan_ttd'] ?? null;
            }

            $this->repository()->updateBeritaAcaraSignature($meetingId, $data);

            return $this->success(null, 'Data TTD Berita Acara berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function uploadDocument(int $meetingId, string $type, array $file): array
    {
        try {

            if (!in_array($type, ['BAP', 'Notulen', 'Foto Kegiatan'], true)) {
                throw new InvalidArgumentException('Jenis dokumen tidak valid.');
            }

            require_once __DIR__ . '/../core/UploadHelper.php';

            $allowedExt = $type === 'Foto Kegiatan'
                ? ['jpg', 'jpeg', 'png']
                : ['pdf', 'doc', 'docx'];

            $allowedMime = $type === 'Foto Kegiatan'
                ? ['image/jpeg', 'image/png']
                : ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

            $uploader = new UploadHelper(
                dirname(__DIR__) . '/uploads/rtm',
                $allowedExt,
                $allowedMime
            );

            $uploaded = $uploader->upload($file);

            $this->repository()->saveDocument($meetingId, $type, $uploaded);

            return $this->success(null, 'Dokumen berhasil diupload.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteDocument(int $docId): array
    {
        $this->repository()->deleteDocument($docId);

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }

private function validateActionPlan(array $data, bool $isSurveyItem = false): void
    {
        if (!$isSurveyItem && $data['checklist_result_id'] <= 0) {
            throw new InvalidArgumentException('Temuan wajib dipilih.');
        }

        if ($data['activity'] === '') {
            throw new InvalidArgumentException('Kegiatan wajib diisi.');
        }
    }

public function saveActionPlan(array $input): array
    {
        try {

            $id = (int)($input['id'] ?? 0);

            $isSurveyItem = false;

            if ($id > 0) {
                $existing = $this->repository()->findActionPlanById($id);
                $isSurveyItem = $existing && ($existing['source_type'] ?? 'audit') === 'survey';
            }

            $data = [
                'rtm_meeting_id'       => (int)($input['rtm_meeting_id'] ?? 0),
                'checklist_result_id'  => $isSurveyItem ? null : (int)($input['checklist_result_id'] ?? 0),
                'importance'           => trim($input['importance'] ?? 'Important'),
                'urgency'              => trim($input['urgency'] ?? 'Urgent'),
                'activity'             => trim($input['activity'] ?? ''),
                'implementation_time'  => trim($input['implementation_time'] ?? ''),
                'pic'                  => trim($input['pic'] ?? ''),
                'budget'               => trim($input['budget'] ?? ''),
                'status'               => trim($input['status'] ?? 'Belum'),
            ];

            $this->validateActionPlan($data, $isSurveyItem);

            if ($id > 0) {
                $this->repository()->updateActionPlan($id, $data);
                return $this->success(['id' => $id], 'RTL berhasil diperbarui.');
            }

            $newId = $this->repository()->saveActionPlan($data);

            return $this->success(['id' => $newId], 'RTL berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteActionPlan(int $id): array
    {
        $this->repository()->deleteActionPlan($id);

        return $this->success(null, 'RTL berhasil dihapus.');
    }
    public function getAllActionPlans(int $periodId = 0, int $unitId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        return $this->success($this->repository()->getAllActionPlans($periodId, $unitId, $status, $limit, $offset));
    }

    public function countAllActionPlans(int $periodId = 0, int $unitId = 0, string $status = ''): array
    {
        return $this->success($this->repository()->countAllActionPlans($periodId, $unitId, $status));
    }

    public function getActionPlanStatistics(int $unitId = 0): array
    {
        return $this->success($this->repository()->getActionPlanStatistics($unitId));
    }
    public function getActionPlanDetail(int $id): array
    {
        $data = $this->repository()->findActionPlanDetail($id);

        if (!$data) {
            return $this->error('RTL tidak ditemukan.');
        }

        $data['evidences'] = $this->repository()->getEvidences($id);

        return $this->success($data);
    }

    public function updateImplementation(int $id, array $input): array
    {
        try {

            $data = [
                'progress_note' => trim($input['progress_note'] ?? ''),
                'status'        => trim($input['status'] ?? 'Proses'),
            ];

            if (!in_array($data['status'], ['Belum', 'Proses', 'Selesai'], true)) {
                throw new InvalidArgumentException('Status tidak valid.');
            }

            $this->repository()->updateImplementation($id, $data);

            return $this->success(null, 'Progres pelaksanaan berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function verifyActionPlan(int $id, string $verificationStatus, string $verificationNote): array
    {
        try {

            if (!in_array($verificationStatus, ['Sesuai', 'Perlu Revisi'], true)) {
                throw new InvalidArgumentException('Hasil verifikasi tidak valid.');
            }

            $verifiedBy = $_SESSION['user_id'] ?? 0;

            $this->repository()->verifyActionPlan($id, $verificationStatus, $verificationNote, $verifiedBy);

            return $this->success(null, 'Verifikasi berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getMonitoringByUnit(int $periodId = 0): array
    {
        return $this->success($this->repository()->getMonitoringByUnit($periodId));
    }

    public function getMonitoringOverall(int $periodId = 0): array
    {
        return $this->success($this->repository()->getMonitoringOverall($periodId));
    }

public function uploadEvidence(int $actionPlanId, array $file, string $linkUrl = ''): array
    {
        try {

            $linkUrl = trim($linkUrl);
            $hasFile = !empty($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if (!$hasFile && $linkUrl === '') {
                throw new InvalidArgumentException('Isi salah satu: Upload File atau Link.');
            }

            if ($linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Format Link tidak valid.');
            }

            $data = [
                'document_file' => null,
                'document_original_name' => null,
                'document_size' => null,
                'link_url' => $linkUrl !== '' ? $linkUrl : null,
            ];

            if ($hasFile) {

                require_once __DIR__ . '/../core/UploadHelper.php';

                $uploader = new UploadHelper(
                    dirname(__DIR__) . '/uploads/rtl_evidence',
                    ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
                    ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                     'image/jpeg', 'image/png']
                );

                $uploaded = $uploader->upload($file);

                $data['document_file'] = $uploaded['document_file'];
                $data['document_original_name'] = $uploaded['document_original_name'];
                $data['document_size'] = $uploaded['document_size'];
            }

            $uploadedBy = $_SESSION['user_id'] ?? 0;

            $this->repository()->saveEvidence($actionPlanId, $data, $uploadedBy);

            return $this->success(null, 'Bukti pelaksanaan berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteEvidence(int $evidenceId): array
    {
        $this->repository()->deleteEvidence($evidenceId);

        return $this->success(null, 'Bukti berhasil dihapus.');
    }

    public function findOrCreateSurveyMeeting(int $unitId, int $periodId, int $surveyYear): int
    {
        $createdBy = $_SESSION['user_id'] ?? 0;
        return $this->repository()->findOrCreateSurveyMeeting($unitId, $periodId, $surveyYear, $createdBy);
    }

    public function existsSurveyActionPlan(int $meetingId, int $surveyCategoryId, int $surveyYear): bool
    {
        return $this->repository()->existsSurveyActionPlan($meetingId, $surveyCategoryId, $surveyYear);
    }

    public function createActionPlanFromSurvey(array $data): int
    {
        return $this->repository()->createActionPlanFromSurvey($data);
    }
}