<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RencanaEvaluasiService extends BaseService
{
    public function __construct(RencanaEvaluasiRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RencanaEvaluasiRepository
    {
        /** @var RencanaEvaluasiRepository */
        return parent::repository();
    }

    public function getAllByMataKuliah(int $mataKuliahId, int $periodeId): array
    {
        return $this->success($this->repository()->getAllByMataKuliah($mataKuliahId, $periodeId));
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
            'mata_kuliah_id'          => (int) ($data['mata_kuliah_id'] ?? 0),
            'periode_id'              => (int) ($data['periode_id'] ?? 0),
            'pertemuan'               => !empty($data['pertemuan']) ? (int) $data['pertemuan'] : null,
            'basis_evaluasi'          => trim($data['basis_evaluasi'] ?? ''),
            'komponen_siakad'         => trim($data['komponen_siakad'] ?? '') ?: null,
            'bobot_persen'            => (float) ($data['bobot_persen'] ?? 0),
            'deskripsi'               => trim($data['deskripsi'] ?? ''),
            'deskripsi_eng'           => trim($data['deskripsi_eng'] ?? ''),
            'sort_order'              => (int) ($data['sort_order'] ?? 0),
            'sub_cpmk_ids'            => $data['sub_cpmk_ids'] ?? [],
            'indikator_kognitif'      => trim($data['indikator_kognitif'] ?? '') ?: null,
            'indikator_afektif'       => trim($data['indikator_afektif'] ?? '') ?: null,
            'indikator_psikomotorik'  => trim($data['indikator_psikomotorik'] ?? '') ?: null,
            'siakad_rincian'          => $this->parseSiakadRincian($data),
        ];
    }

    private function parseSiakadRincian(array $data): array
    {
        $komponenList = $data['siakad_komponen'] ?? [];
        $bobotList = $data['siakad_bobot'] ?? [];
        $rincian = [];

        foreach ($komponenList as $idx => $komponen) {
            $rincian[] = [
                'komponen' => $komponen,
                'bobot'    => (float) ($bobotList[$idx] ?? 0),
            ];
        }

        return $rincian;
    }

    private function validate(array $data): void
    {
        if ($data['mata_kuliah_id'] <= 0) {
            throw new InvalidArgumentException('Mata Kuliah tidak valid.');
        }

        if ($data['periode_id'] <= 0) {
            throw new InvalidArgumentException('Periode Akademik tidak valid.');
        }

        if ($data['basis_evaluasi'] === '') {
            throw new InvalidArgumentException('Basis Evaluasi wajib diisi.');
        }

        if ($data['bobot_persen'] < 0 || $data['bobot_persen'] > 100) {
            throw new InvalidArgumentException('Bobot harus di antara 0 - 100.');
        }
    }

    private const BATAS_BOBOT_PER_BASIS = [
        'Aktivitas Partisipatif' => 25,
        'Hasil Proyek'           => 25,
        'Tugas'                  => 10,
        'UTS'                    => 20,
        'UAS'                    => 20,
    ];

    private function cekBatasPerBasisEvaluasi(array $data, int $excludeId = 0): void
    {
        $basis = $data['basis_evaluasi'];

        if (!isset(self::BATAS_BOBOT_PER_BASIS[$basis])) {
            return; // Basis Evaluasi di luar 5 daftar baku, tidak dibatasi
        }

        $batas = self::BATAS_BOBOT_PER_BASIS[$basis];
        $totalExistingBasis = $this->repository()->getTotalBobotByBasisEvaluasi($data['mata_kuliah_id'], $data['periode_id'], $basis, $excludeId);

        if (($totalExistingBasis + $data['bobot_persen']) > $batas + 0.01) {
            throw new InvalidArgumentException(
                "Total Bobot untuk \"{$basis}\" akan melebihi batas maksimal {$batas}% (saat ini sudah {$totalExistingBasis}% dari Pertemuan lain)."
            );
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobot($data['mata_kuliah_id'], $data['periode_id']);

            if (($totalExisting + $data['bobot_persen']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot Rencana Evaluasi Mata Kuliah ini akan melebihi 100% (saat ini sudah ' . $totalExisting . '%).');
            }

            $this->cekBatasPerBasisEvaluasi($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Rencana Evaluasi berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobot($data['mata_kuliah_id'], $data['periode_id'], $id);

            if (($totalExisting + $data['bobot_persen']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot Rencana Evaluasi Mata Kuliah ini akan melebihi 100% (baris lain sudah ' . $totalExisting . '%).');
            }

            $this->cekBatasPerBasisEvaluasi($data, $id);

            $this->repository()->update($id, $data);

            return $this->success(null, 'Rencana Evaluasi berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Rencana Evaluasi berhasil dihapus.');
    }
}