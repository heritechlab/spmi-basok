<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class DiskusiCplService extends BaseService
{
    public function __construct(DiskusiCplRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): DiskusiCplRepository
    {
        /** @var DiskusiCplRepository */
        return parent::repository();
    }

    public function getByMahasiswa(int $mahasiswaId): array
    {
        return $this->success($this->repository()->getByMahasiswa($mahasiswaId));
    }

    public function create(int $mahasiswaId, string $penulisNama, string $pesan): array
    {
        $pesan = trim($pesan);

        if ($pesan === '') {
            return $this->error('Pesan tidak boleh kosong.');
        }

        if ($mahasiswaId <= 0) {
            return $this->error('Mahasiswa tidak valid.');
        }

        $id = $this->repository()->create($mahasiswaId, $penulisNama, $pesan);

        return $this->success(['id' => $id], 'Pesan terkirim.');
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Pesan dihapus.');
    }
}