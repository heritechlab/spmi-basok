<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class PtpService extends BaseService
{
    public function __construct(PtpRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): PtpRepository
    {
        /** @var PtpRepository */
        return parent::repository();
    }

    public function getEligibleItems(int $unitId, int $periodId): array
    {
        return $this->success($this->repository()->getEligibleItems($unitId, $periodId));
    }

    public function getAll(int $unitId, int $periodId = 0, string $status = '', int $limit = 10, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($unitId, $periodId, $status, $limit, $offset));
    }

    public function count(int $unitId, int $periodId = 0, string $status = ''): array
    {
        return $this->success($this->repository()->count($unitId, $periodId, $status));
    }

    public function getStatistics(int $unitId): array
    {
        return $this->success($this->repository()->getStatistics($unitId));
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data rapat PTP tidak ditemukan.');
        }

        $data['participants'] = $this->repository()->getParticipants($id);
        $data['items'] = $this->repository()->getItems($id);

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'period_id'      => (int)($data['period_id'] ?? 0),
            'unit_id'        => (int)($data['unit_id'] ?? 0),
            'meeting_number' => trim($data['meeting_number'] ?? ''),
            'meeting_date'   => !empty($data['meeting_date']) ? $data['meeting_date'] : null,
            'venue'          => trim($data['venue'] ?? ''),
            'start_time'     => !empty($data['start_time']) ? $data['start_time'] : null,
            'end_time'       => !empty($data['end_time']) ? $data['end_time'] : null,
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

        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Unit Kerja wajib dipilih.');
        }

        if ($data['meeting_number'] === '') {
            throw new InvalidArgumentException('Nomor Rapat wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsNumber($data['meeting_number'])) {
                throw new InvalidArgumentException('Nomor Rapat sudah digunakan.');
            }

            $data['created_by'] = $_SESSION['user_id'] ?? 0;

            $id = $this->repository()->create($data);

            $this->saveParticipantsFromInput($id, $input);

            return $this->success(['id' => $id], 'Rapat PTP berhasil ditambahkan.');

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
                throw new InvalidArgumentException('Nomor Rapat sudah digunakan.');
            }

            $this->repository()->update($id, $data);

            $this->saveParticipantsFromInput($id, $input);

            return $this->success(null, 'Rapat PTP berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    private function saveParticipantsFromInput(int $meetingId, array $input): void
    {
        $names = $input['participant_name'] ?? [];
        $positions = $input['participant_position'] ?? [];
        $userIds = $input['participant_user_id'] ?? [];

        if (!is_array($names)) {
            return;
        }

        $participants = [];

        foreach ($names as $i => $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $participants[] = [
                'user_id'   => $userIds[$i] ?? null,
                'full_name' => $name,
                'position'  => trim($positions[$i] ?? ''),
            ];
        }

        $this->repository()->saveParticipants($meetingId, $participants);
    }

    public function saveItem(array $input): array
    {
        try {

            $data = [
                'ptp_meeting_id'       => (int)($input['ptp_meeting_id'] ?? 0),
                'audit_indicator_id'   => (int)($input['audit_indicator_id'] ?? 0),
                'checklist_result_id'  => !empty($input['checklist_result_id']) ? (int)$input['checklist_result_id'] : null,
                'old_indicator'        => trim($input['old_indicator'] ?? ''),
                'new_indicator'        => trim($input['new_indicator'] ?? ''),
                'old_statement'        => trim($input['old_statement'] ?? ''),
                'new_statement'        => trim($input['new_statement'] ?? ''),
                'old_target'           => trim($input['old_target'] ?? ''),
                'new_target'           => trim($input['new_target'] ?? ''),
            ];

            if ($data['audit_indicator_id'] <= 0) {
                throw new InvalidArgumentException('Indikator wajib dipilih.');
            }

            if ($data['new_target'] === '' && $data['new_statement'] === '' && $data['new_indicator'] === '') {
                throw new InvalidArgumentException('Isi minimal salah satu perubahan (Target/Pernyataan/Indikator).');
            }

            $id = $this->repository()->saveItem($data);

            return $this->success(['id' => $id], 'Usulan peningkatan berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteItem(int $id): array
    {
        $this->repository()->deleteItem($id);

        return $this->success(null, 'Usulan berhasil dihapus.');
    }

    public function applyItem(int $id): array
    {
        $result = $this->repository()->applyItem($id);

        if (!$result) {
            return $this->error('Usulan tidak ditemukan.');
        }

        return $this->success(null, 'Perubahan berhasil diterapkan ke Master Indikator.');
    }

    public function findOrCreateSurveyMeeting(int $unitId, int $periodId, int $surveyYear): int
    {
        $createdBy = $_SESSION['user_id'] ?? 0;
        return $this->repository()->findOrCreateSurveyMeeting($unitId, $periodId, $surveyYear, $createdBy);
    }

    public function existsSurveyItem(int $meetingId, int $surveyCategoryId, int $surveyYear): bool
    {
        return $this->repository()->existsSurveyItem($meetingId, $surveyCategoryId, $surveyYear);
    }

    public function createItemFromSurvey(array $data): int
    {
        return $this->repository()->createItemFromSurvey($data);
    }
}