<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class ProfilProdiService extends BaseService
{
    public function __construct(ProfilProdiRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): ProfilProdiRepository
    {
        /** @var ProfilProdiRepository */
        return parent::repository();
    }

    public function getByUnit(int $unitId): array
    {
        $data = $this->repository()->getByUnit($unitId);

        return $this->success($data ?: ['unit_id' => $unitId, 'visi' => '', 'misi' => '', 'unggulan' => '']);
    }

    public function save(int $unitId, array $input): array
    {
        if ($unitId <= 0) {
            return $this->error('Program Studi wajib dipilih.');
        }

        $visi = trim($input['visi'] ?? '') ?: null;
        $misi = trim($input['misi'] ?? '') ?: null;
        $unggulan = trim($input['unggulan'] ?? '') ?: null;

        $this->repository()->upsert($unitId, $visi, $misi, $unggulan);

        return $this->success(null, 'Profil Program Studi berhasil disimpan.');
    }
}