<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class GkmService extends BaseService
{
    public function __construct(GkmRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): GkmRepository
    {
        /** @var GkmRepository */
        return parent::repository();
    }

    public function getAll(int $unitId = 0, string $semester = '', string $academicYear = '', int $limit = 20, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($unitId, $semester, $academicYear, $limit, $offset));
    }

    public function count(int $unitId = 0, string $semester = '', string $academicYear = ''): array
    {
        return $this->success($this->repository()->count($unitId, $semester, $academicYear));
    }

public function create(int $unitId, string $semester, string $academicYear, string $gkmNama, int $createdBy): array
    {
        if ($unitId <= 0) {
            return $this->error('Unit Kerja wajib dipilih.');
        }

        if (!in_array($semester, ['Ganjil', 'Genap'], true)) {
            return $this->error('Semester wajib dipilih.');
        }

        if (trim($academicYear) === '') {
            return $this->error('Tahun Akademik wajib diisi.');
        }

        if ($this->repository()->existsForUnit($unitId, $semester, $academicYear)) {
            return $this->error('Data Monitoring untuk Unit, Semester, dan Tahun Akademik ini sudah ada.');
        }

        $id = $this->repository()->create($unitId, $semester, $academicYear, trim($gkmNama), $createdBy);

        return $this->success(['id' => $id], 'Monitoring GKM berhasil dibuat.');
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Data Monitoring berhasil dihapus.');
    }

    public function getDetail(int $id): array
    {
        $monitoring = $this->repository()->findById($id);

        if (!$monitoring) {
            return $this->error('Data Monitoring tidak ditemukan.');
        }

        $items = $this->repository()->getChecklistItems();
        $responses = $this->repository()->getItemResponses($id);

        $grouped = ['Perencanaan' => [], 'Proses' => [], 'Pelaporan' => []];

        foreach ($items as $item) {
            $itemId = (int) $item['id'];
            $response = $responses[$itemId] ?? ['status' => 'Belum', 'catatan' => ''];

            $grouped[$item['tahap']][] = [
                'id' => $itemId,
                'item_text' => $item['item_text'],
                'status' => $response['status'],
                'catatan' => $response['catatan'],
            ];
        }

$statsMap = $this->normalizeStats($this->repository()->getCompletionStats($id));

        return $this->success([
            'monitoring' => $monitoring,
            'items' => $grouped,
            'stats' => $statsMap,
        ]);
    }

    public function saveResponses(int $monitoringId, array $responses): array
    {
        foreach ($responses as $itemId => $data) {

            $status = ($data['status'] ?? 'Belum') === 'Sudah' ? 'Sudah' : 'Belum';
            $catatan = trim($data['catatan'] ?? '');

            $this->repository()->saveItemResponse($monitoringId, (int) $itemId, $status, $catatan);
        }

        return $this->success(null, 'Checklist berhasil disimpan.');
    }
private function normalizeStats(array $rawStats): array
    {
        $statsMap = ['Perencanaan' => ['total' => 0, 'done' => 0, 'percent' => 0], 'Proses' => ['total' => 0, 'done' => 0, 'percent' => 0], 'Pelaporan' => ['total' => 0, 'done' => 0, 'percent' => 0]];

        foreach ($rawStats as $s) {
            $total = (int) $s['total_item'];
            $done = (int) $s['done_item'];

            $statsMap[$s['tahap']] = [
                'total' => $total,
                'done' => $done,
                'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
            ];
        }

        return $statsMap;
    }

    public function getInstitutionSummary(string $semester, string $academicYear): array
    {
        $monitorings = $this->repository()->getInstitutionSummary($semester, $academicYear);

        $tahapTotals = ['Perencanaan' => ['done' => 0, 'total' => 0], 'Proses' => ['done' => 0, 'total' => 0], 'Pelaporan' => ['done' => 0, 'total' => 0]];

        foreach ($monitorings as &$m) {

            $m['stats'] = $this->normalizeStats($m['stats']);

            foreach ($m['stats'] as $tahap => $st) {
                $tahapTotals[$tahap]['done'] += $st['done'];
                $tahapTotals[$tahap]['total'] += $st['total'];
            }
        }
        unset($m);

        $institutionPercent = [];

        foreach ($tahapTotals as $tahap => $t) {
            $institutionPercent[$tahap] = $t['total'] > 0 ? round(($t['done'] / $t['total']) * 100) : 0;
        }

        return $this->success([
            'monitorings' => $monitorings,
            'institution_percent' => $institutionPercent,
        ]);
    }
    public function saveSignatures(int $id, array $input, array $files): array
    {
        $monitoring = $this->repository()->findById($id);

        if (!$monitoring) {
            return $this->error('Data Monitoring tidak ditemukan.');
        }

        require_once __DIR__ . '/../core/UploadHelper.php';

        $data = [
            'gkm_nama'    => trim($input['gkm_nama'] ?? ''),
            'gkm_jabatan' => trim($input['gkm_jabatan'] ?? 'Ketua Gugus Kendali Mutu'),
            'gkm_tanggal' => !empty($input['gkm_tanggal']) ? $input['gkm_tanggal'] : null,
            'lpm_nama'    => trim($input['lpm_nama'] ?? ''),
            'lpm_tanggal' => !empty($input['lpm_tanggal']) ? $input['lpm_tanggal'] : null,
        ];

        $uploadRoot = dirname(__DIR__) . '/uploads/signatures';

        if (!empty($files['gkm_ttd']['name'])) {
            $uploader = new UploadHelper($uploadRoot, ['jpg', 'jpeg', 'png'], ['image/jpeg', 'image/png']);
            $uploaded = $uploader->upload($files['gkm_ttd']);
            $data['gkm_ttd'] = $uploaded['document_file'];
        } else {
            $data['gkm_ttd'] = $monitoring['gkm_ttd'] ?? null;
        }

        if (!empty($files['lpm_ttd']['name'])) {
            $uploader = new UploadHelper($uploadRoot, ['jpg', 'jpeg', 'png'], ['image/jpeg', 'image/png']);
            $uploaded = $uploader->upload($files['lpm_ttd']);
            $data['lpm_ttd'] = $uploaded['document_file'];
        } else {
            $data['lpm_ttd'] = $monitoring['lpm_ttd'] ?? null;
        }

        $this->repository()->updateSignatures($id, $data);

        return $this->success(null, 'Data pengesahan berhasil disimpan.');
    }
}