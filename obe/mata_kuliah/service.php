<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class MataKuliahService extends BaseService
{
    public function __construct(MataKuliahRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): MataKuliahRepository
    {
        /** @var MataKuliahRepository */
        return parent::repository();
    }

    public function getAll(int $kurikulumId, int $periodeId): array
    {
        return $this->success($this->repository()->getAll($kurikulumId, $periodeId));
    }

        public function getMatriksCplMk(int $unitId): array
    {
        return $this->success($this->repository()->getMatriksCplMk($unitId));
    }

    public function getCplList(int $unitId): array
    {
        require_once __DIR__ . '/../cpl/repository.php';
        require_once __DIR__ . '/../cpl/service.php';

        global $conn;
        $cplRepo = new CplRepository($conn);
        $cplService = new CplService($cplRepo);

        return $cplService->getAll($unitId);
    }

    public function getById(int $id, int $periodeId): array
    {
        $data = $this->repository()->findById($id, $periodeId);

        if (!$data) {
            return $this->error('Data tidak ditemukan.');
        }

        return $this->success($data);
    }

     private function normalize(array $data): array
    {
        $dosenIds = $data['dosen_ids'] ?? [];
        $koordinatorId = (int) ($data['koordinator_id'] ?? 0);

        $dosenList = [];
        foreach ($dosenIds as $dosenId) {
            $dosenId = (int) $dosenId;
            $dosenList[] = [
                'dosen_id' => $dosenId,
                'peran'    => $dosenId === $koordinatorId ? 'Koordinator' : 'Anggota',
            ];
        }

        return [
            'unit_id'                 => (int) ($data['unit_id'] ?? 0),
            'kurikulum_id'            => (int) ($data['kurikulum_id'] ?? 0),
            'periode_id'              => (int) ($data['periode_id'] ?? 0),
            'code'                    => trim($data['code'] ?? '') ?: null,
            'name'                    => trim($data['name'] ?? ''),
            'semester'                => (int) ($data['semester'] ?? 1),
            'jenis_mk'                => trim($data['jenis_mk'] ?? 'Wajib Prodi'),
            'kelompok_mk'             => trim($data['kelompok_mk'] ?? '') ?: null,
            'konsentrasi'             => trim($data['konsentrasi'] ?? '') ?: null,
            'sks_tatap_muka'          => (int) ($data['sks_tatap_muka'] ?? 0),
            'sks_praktikum'           => (int) ($data['sks_praktikum'] ?? 0),
            'sks_praktek_lapangan'    => (int) ($data['sks_praktek_lapangan'] ?? 0),
            'sks_simulasi'            => (int) ($data['sks_simulasi'] ?? 0),
            'minimal_nilai_lulus'     => trim($data['minimal_nilai_lulus'] ?? 'C'),
            'rumpun_mk'               => trim($data['rumpun_mk'] ?? '') ?: null,
            'dosen_pengembang_rps_id' => !empty($data['dosen_pengembang_rps_id']) ? (int) $data['dosen_pengembang_rps_id'] : null,
            'ada_diktat'              => !empty($data['ada_diktat']) ? 1 : 0,
            'ada_silabus'             => !empty($data['ada_silabus']) ? 1 : 0,
            'validasi_rps'            => trim($data['validasi_rps'] ?? 'Belum'),
            'tahun_ajaran'            => trim($data['tahun_ajaran'] ?? '') ?: null,
            'tanggal_revisi_rps'      => trim($data['tanggal_revisi_rps'] ?? '') ?: null,
            'gkm_dosen_id'            => !empty($data['gkm_dosen_id']) ? (int) $data['gkm_dosen_id'] : null,
            'deskripsi'               => trim($data['deskripsi'] ?? '') ?: null,
            'media_pembelajaran'      => trim($data['media_pembelajaran'] ?? '') ?: null,
            'prasyarat_mk'            => trim($data['prasyarat_mk'] ?? '') ?: null,
            'pustaka_utama'           => trim($data['pustaka_utama'] ?? '') ?: null,
            'pustaka_pendukung'       => trim($data['pustaka_pendukung'] ?? '') ?: null,
            'cpl_ids'                 => $data['cpl_ids'] ?? [],
            'dosen_list'              => $dosenList,
        ];
    }

    private function validate(array $data): void
    {
        $allowedJenis = ['Wajib Nasional', 'Wajib Institusi', 'Wajib Prodi', 'Pilihan Prodi'];

        if ($data['unit_id'] <= 0) {
            throw new InvalidArgumentException('Program Studi wajib dipilih.');
        }

        if ($data['kurikulum_id'] <= 0) {
            throw new InvalidArgumentException('Kurikulum wajib dipilih.');
        }

        if ($data['periode_id'] <= 0) {
            throw new InvalidArgumentException('Periode Akademik tidak valid.');
        }

        if ($data['name'] === '') {
            throw new InvalidArgumentException('Nama Mata Kuliah wajib diisi.');
        }

        if ($data['semester'] < 1 || $data['semester'] > 14) {
            throw new InvalidArgumentException('Semester tidak valid.');
        }

        if (!in_array($data['jenis_mk'], $allowedJenis, true)) {
            throw new InvalidArgumentException('Jenis Mata Kuliah tidak valid.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Mata Kuliah berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);
            $this->validate($data);

            $this->repository()->update($id, $data);

            return $this->success(null, 'Mata Kuliah berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Mata Kuliah berhasil dihapus.');
    }
}