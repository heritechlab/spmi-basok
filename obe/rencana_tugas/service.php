<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RencanaTugasService extends BaseService
{
    public function __construct(RencanaTugasRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RencanaTugasRepository
    {
        /** @var RencanaTugasRepository */
        return parent::repository();
    }

    public function getForMataKuliah(int $mkId, int $periodeId): array
    {
        $qualifying = $this->repository()->getQualifyingRencanaEvaluasi($mkId, $periodeId);
        $existingRows = $this->repository()->getByMataKuliah($mkId, $periodeId);

        $existingMap = [];
        foreach ($existingRows as $row) {
            $existingMap[(int) $row['rencana_evaluasi_id']] = $row;
        }

        $result = [];
        $tugasKe = 0;

        foreach ($qualifying as $reo) {
            $tugasKe++;
            $existing = $existingMap[(int) $reo['id']] ?? null;

            $result[] = [
                'rencana_evaluasi_id' => (int) $reo['id'],
                'tugas_ke'            => $tugasKe,
                'pertemuan'           => $reo['pertemuan'] !== null ? (int) $reo['pertemuan'] : null,
                'basis_evaluasi'      => $reo['basis_evaluasi'],
                'bobot_persen'        => (float) $reo['bobot_persen'],
                'sub_cpmk_list'       => $reo['sub_cpmk_list'],
                'indikator_list'      => $reo['indikator_list'],
                'bentuk_tugas'        => $existing['bentuk_tugas'] ?? '',
                'judul'               => $existing['judul'] ?? '',
                'deskripsi_tugas'     => $existing['deskripsi_tugas'] ?? '',
                'metode_pengerjaan'   => $existing['metode_pengerjaan'] ?? '',
                'bentuk_luaran'       => $existing['bentuk_luaran'] ?? '',
                'kriteria_penilaian'  => $existing['kriteria_penilaian'] ?? '',
                'minggu_mulai'        => $existing['minggu_mulai'] ?? $reo['pertemuan'],
                'minggu_selesai'      => $existing['minggu_selesai'] ?? $reo['pertemuan'],
            ];
        }

        return $this->success($result);
    }

    public function save(int $mkId, int $periodeId, array $input): array
    {
        $reoId = (int) ($input['rencana_evaluasi_id'] ?? 0);

        if ($reoId <= 0) {
            return $this->error('Rencana Evaluasi tidak valid.');
        }

        try {
            $this->repository()->upsert([
                'mata_kuliah_id'      => $mkId,
                'periode_id'          => $periodeId,
                'rencana_evaluasi_id' => $reoId,
                'bentuk_tugas'        => trim($input['bentuk_tugas'] ?? '') ?: null,
                'judul'               => trim($input['judul'] ?? '') ?: null,
                'deskripsi_tugas'     => trim($input['deskripsi_tugas'] ?? '') ?: null,
                'metode_pengerjaan'   => trim($input['metode_pengerjaan'] ?? '') ?: null,
                'bentuk_luaran'       => trim($input['bentuk_luaran'] ?? '') ?: null,
                'kriteria_penilaian'  => trim($input['kriteria_penilaian'] ?? '') ?: null,
                'minggu_mulai'        => !empty($input['minggu_mulai']) ? (int) $input['minggu_mulai'] : null,
                'minggu_selesai'      => !empty($input['minggu_selesai']) ? (int) $input['minggu_selesai'] : null,
            ]);

            return $this->success(null, 'Rencana Tugas berhasil disimpan.');
        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}