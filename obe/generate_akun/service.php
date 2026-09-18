<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class GenerateAkunService extends BaseService
{
    public function __construct(GenerateAkunRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): GenerateAkunRepository
    {
        /** @var GenerateAkunRepository */
        return parent::repository();
    }

    public function getPreviewDosen(int $unitId): array
    {
        return $this->success($this->repository()->getDosenTanpaAkun($unitId));
    }

    public function getPreviewMahasiswa(int $kurikulumId): array
    {
        return $this->success($this->repository()->getMahasiswaTanpaAkun($kurikulumId));
    }

    public function generateDosen(int $unitId, array $dosenIds): array
    {
        $calon = $this->repository()->getDosenTanpaAkun($unitId);
        $calonMap = [];
        foreach ($calon as $c) {
            $calonMap[$c['id']] = $c;
        }

        $berhasil = 0;
        $gagal = [];

        foreach ($dosenIds as $id) {
            $id = (int) $id;
            if (!isset($calonMap[$id])) continue;

            $dosen = $calonMap[$id];
            $username = trim($dosen['nidn']);

            if ($username === '' || $this->repository()->usernameExists($username)) {
                $gagal[] = $dosen['name'] . ' (NIDN kosong atau username sudah dipakai)';
                continue;
            }

            $fullName = trim(($dosen['gelar_depan'] ?? '') . ' ' . $dosen['name'] . ($dosen['gelar_belakang'] ? ', ' . $dosen['gelar_belakang'] : ''));
            $hash = password_hash($username, PASSWORD_DEFAULT);

            $this->repository()->createUserDosen($id, $unitId, $username, $fullName, $hash);
            $berhasil++;
        }

        return $this->success(['berhasil' => $berhasil, 'gagal' => $gagal], "{$berhasil} akun Dosen berhasil dibuat.");
    }

    public function generateMahasiswa(int $unitId, int $kurikulumId, array $mahasiswaIds): array
    {
        $calon = $this->repository()->getMahasiswaTanpaAkun($kurikulumId);
        $calonMap = [];
        foreach ($calon as $c) {
            $calonMap[$c['id']] = $c;
        }

        $berhasil = 0;
        $gagal = [];

        foreach ($mahasiswaIds as $id) {
            $id = (int) $id;
            if (!isset($calonMap[$id])) continue;

            $mhs = $calonMap[$id];
            $username = trim($mhs['nim']);

            if ($username === '' || $this->repository()->usernameExists($username)) {
                $gagal[] = $mhs['nama'] . ' (NIM kosong atau username sudah dipakai)';
                continue;
            }

            $hash = password_hash($username, PASSWORD_DEFAULT);

            $this->repository()->createUserMahasiswa($id, $unitId, $username, $mhs['nama'], $hash);
            $berhasil++;
        }

        return $this->success(['berhasil' => $berhasil, 'gagal' => $gagal], "{$berhasil} akun Mahasiswa berhasil dibuat.");
    }
}