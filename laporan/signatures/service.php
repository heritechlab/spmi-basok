<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class LaporanSignatureService extends BaseService
{
    public function __construct(LaporanSignatureRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): LaporanSignatureRepository
    {
        /** @var LaporanSignatureRepository */
        return parent::repository();
    }

    public function find(string $reportType, int $periodId, ?int $unitId = null): array
    {
        $data = $this->repository()->find($reportType, $periodId, $unitId);

        return $this->success($data ?? []);
    }

    public function save(array $input, array $files): array
    {
        try {

            $reportType = trim($input['report_type'] ?? '');
            $periodId = (int) ($input['period_id'] ?? 0);
            $unitId = !empty($input['unit_id']) ? (int) $input['unit_id'] : null;

if (!in_array($reportType, ['unit', 'institusi', 'rtm', 'ptp', 'led'], true)) {
                throw new InvalidArgumentException('Jenis laporan tidak valid.');
            }

            if ($periodId <= 0) {
                throw new InvalidArgumentException('Periode wajib dipilih.');
            }

if (in_array($reportType, ['unit', 'rtm', 'ptp', 'led'], true) && !$unitId) {
                throw new InvalidArgumentException('Unit Kerja wajib dipilih.');
            }

            $existing = $this->repository()->find($reportType, $periodId, $unitId) ?? [];

            require_once __DIR__ . '/../../core/UploadHelper.php';
            $uploadRoot = dirname(__DIR__, 2) . '/uploads/signatures';

            $data = [
                'report_type' => $reportType,
                'unit_id'     => $unitId,
                'period_id'   => $periodId,

                'ketua_tim_nama'    => trim($input['ketua_tim_nama'] ?? ''),
                'ketua_tim_jabatan' => trim($input['ketua_tim_jabatan'] ?? 'Ketua Tim Audit'),
                'ketua_tim_tanggal' => !empty($input['ketua_tim_tanggal']) ? $input['ketua_tim_tanggal'] : null,

                'ketua_lpm_nama'    => trim($input['ketua_lpm_nama'] ?? ''),
                'ketua_lpm_tanggal' => !empty($input['ketua_lpm_tanggal']) ? $input['ketua_lpm_tanggal'] : null,

                'ketua_institusi_nama'    => trim($input['ketua_institusi_nama'] ?? ''),
                'ketua_institusi_tanggal' => !empty($input['ketua_institusi_tanggal']) ? $input['ketua_institusi_tanggal'] : null,
            ];

            foreach (['ketua_tim_ttd', 'ketua_lpm_ttd', 'ketua_institusi_ttd'] as $fieldKey) {

                if (!empty($files[$fieldKey]['name'])) {
                    $uploader = new UploadHelper($uploadRoot, ['jpg', 'jpeg', 'png'], ['image/jpeg', 'image/png']);
                    $uploaded = $uploader->upload($files[$fieldKey]);
                    $data[$fieldKey] = $uploaded['document_file'];
                } else {
                    $data[$fieldKey] = $existing[$fieldKey] ?? null;
                }
            }

            $this->repository()->upsert($data);

            return $this->success(null, 'Data TTD berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}