<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class CapaianPembelajaranService extends BaseService
{
    public function __construct(CapaianPembelajaranRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): CapaianPembelajaranRepository
    {
        /** @var CapaianPembelajaranRepository */
        return parent::repository();
    }

    public function getAllByMataKuliah(int $mataKuliahId): array
    {
        return $this->success($this->repository()->getAllByMataKuliah($mataKuliahId));
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
            'mata_kuliah_id'        => (int) ($data['mata_kuliah_id'] ?? 0),
            'pertemuan'             => (int) ($data['pertemuan'] ?? 0),
            'cpmk_id'               => (int) ($data['cpmk_id'] ?? 0),
            'sub_cpmk_id'           => !empty($data['sub_cpmk_id']) ? (int) $data['sub_cpmk_id'] : null,
            'indikator_penilaian'   => trim($data['indikator_penilaian'] ?? ''),
            'bentuk_evaluasi'       => trim($data['bentuk_evaluasi'] ?? ''),
            'bobot_per_evaluasi'    => (float) ($data['bobot_per_evaluasi'] ?? 0),
            'bobot_penilaian'       => (float) ($data['bobot_penilaian'] ?? 0),
            'sort_order'            => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validate(array $data): void
    {
        if ($data['mata_kuliah_id'] <= 0) {
            throw new InvalidArgumentException('Mata Kuliah tidak valid.');
        }

        if ($data['pertemuan'] < 1 || $data['pertemuan'] > 16) {
            throw new InvalidArgumentException('Pertemuan harus antara 1 - 16.');
        }

        if ($data['cpmk_id'] <= 0) {
            throw new InvalidArgumentException('CPMK wajib dipilih.');
        }

        if ($data['bobot_penilaian'] < 0 || $data['bobot_penilaian'] > 100) {
            throw new InvalidArgumentException('Bobot Penilaian harus di antara 0 - 100.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobotPenilaian($data['mata_kuliah_id']);

            if (($totalExisting + $data['bobot_penilaian']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot Penilaian Mata Kuliah ini akan melebihi 100% (saat ini sudah ' . $totalExisting . '%).');
            }

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Capaian Pembelajaran berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $totalExisting = $this->repository()->getTotalBobotPenilaian($data['mata_kuliah_id'], $id);

            if (($totalExisting + $data['bobot_penilaian']) > 100.01) {
                throw new InvalidArgumentException('Total Bobot Penilaian Mata Kuliah ini akan melebihi 100% (baris lain sudah ' . $totalExisting . '%).');
            }

            $this->repository()->update($id, $data);

            return $this->success(null, 'Capaian Pembelajaran berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Capaian Pembelajaran berhasil dihapus.');
    }
}