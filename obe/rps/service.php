<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RpsService extends BaseService
{
    public function __construct(RpsRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RpsRepository
    {
        /** @var RpsRepository */
        return parent::repository();
    }

    public function getAllByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        return $this->success($this->repository()->getAllByMataKuliah($mataKuliahId, $periodeId));
    }

    public function getRencanaEvaluasiList(int $mataKuliahId, int $periodeId): array
    {
        return $this->success($this->repository()->getRencanaEvaluasiListByMataKuliah($mataKuliahId, $periodeId));
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'mata_kuliah_id'       => (int) ($data['mata_kuliah_id'] ?? 0),
            'periode_id'           => (int) ($data['periode_id'] ?? 0),
            'pertemuan'            => (int) ($data['pertemuan'] ?? 0),
            'sub_cpmk_id'          => (int) ($data['sub_cpmk_id'] ?? 0),
            'rencana_evaluasi_ids' => $data['rencana_evaluasi_ids'] ?? [],
            'indikator_umum'       => trim($data['indikator_umum'] ?? ''),
            'indikator_khusus'     => trim($data['indikator_khusus'] ?? ''),
            'alokasi_waktu_rincian' => trim($data['alokasi_waktu_rincian'] ?? ''),
            'materi_pembelajaran'  => trim($data['materi_pembelajaran'] ?? ''),
            'pustaka'              => trim($data['pustaka'] ?? ''),
            'pengalaman_belajar'   => trim($data['pengalaman_belajar'] ?? ''),
            'alokasi_waktu_menit'  => (int) ($data['alokasi_waktu_menit'] ?? 0),
            'bobot_penilaian'      => 0,
            'sort_order'           => (int) ($data['sort_order'] ?? 0),
            'bentuk_luring_ids'    => $data['bentuk_luring_ids'] ?? [],
            'metode_luring_ids'    => $data['metode_luring_ids'] ?? [],
            'metode_daring_ids'    => $data['metode_daring_ids'] ?? [],
            'bahan_kajian_ids'     => $data['bahan_kajian_ids'] ?? [],
        ];
    }

    private function validate(array $data): void
    {
        if ($data['mata_kuliah_id'] <= 0) {
            throw new InvalidArgumentException('Mata Kuliah tidak valid.');
        }

        if ($data['periode_id'] <= 0) {
            throw new InvalidArgumentException('Periode Akademik tidak valid.');
        }

        if ($data['pertemuan'] < 1 || $data['pertemuan'] > 16) {
            throw new InvalidArgumentException('Pertemuan harus antara 1 - 16.');
        }

        if ($data['sub_cpmk_id'] <= 0) {
            throw new InvalidArgumentException('Sub-CPMK wajib dipilih.');
        }

        if (empty($data['rencana_evaluasi_ids'])) {
            throw new InvalidArgumentException('Minimal 1 Rencana Evaluasi wajib dipilih untuk Pertemuan ini.');
        }
    }

        private function resyncBobotPenilaian(array $rencanaEvaluasiIds): void
    {
        $rencanaEvaluasiIds = array_unique(array_filter(array_map('intval', $rencanaEvaluasiIds)));

        if (empty($rencanaEvaluasiIds)) {
            return;
        }

        $affectedRpsIds = $this->repository()->getRpsIdsLinkedToRencanaEvaluasi($rencanaEvaluasiIds);

        foreach ($affectedRpsIds as $rpsId) {

            $linkedRencanaEvaluasi = $this->repository()->getMappedRencanaEvaluasi((int) $rpsId);
            $totalBobot = 0;

            foreach ($linkedRencanaEvaluasi as $re) {
                $jumlahPemakai = $this->repository()->countRpsUsingRencanaEvaluasi((int) $re['id']);
                if ($jumlahPemakai > 0) {
                    $totalBobot += ((float) $re['bobot_persen']) / $jumlahPemakai;
                }
            }

            $this->repository()->updateBobotPenilaian((int) $rpsId, round($totalBobot, 2));
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            $this->repository()->saveRencanaEvaluasiMapping($id, $data['rencana_evaluasi_ids']);
            $this->resyncBobotPenilaian($data['rencana_evaluasi_ids']);

            return $this->success(['id' => $id], 'RPS berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $oldLinked = $this->repository()->getMappedRencanaEvaluasi($id);
            $oldIds = array_column($oldLinked, 'id');

            $data = $this->normalize($input);
            $this->validate($data);

            $this->repository()->update($id, $data);

            $this->repository()->saveRencanaEvaluasiMapping($id, $data['rencana_evaluasi_ids']);
            $this->resyncBobotPenilaian(array_merge($oldIds, $data['rencana_evaluasi_ids']));

            return $this->success(null, 'RPS berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $linked = $this->repository()->getMappedRencanaEvaluasi($id);
        $linkedIds = array_column($linked, 'id');

        $this->repository()->delete($id);

        $this->resyncBobotPenilaian($linkedIds);

        return $this->success(null, 'RPS berhasil dihapus.');
    }

    public function getRencanaEvaluasiByPertemuan(int $mataKuliahId, int $periodeId, int $pertemuan): array
    {
        return $this->success($this->repository()->getRencanaEvaluasiListByPertemuan($mataKuliahId, $periodeId, $pertemuan));
    }
}