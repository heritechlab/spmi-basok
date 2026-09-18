<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../rencana_tugas/repository.php';
require_once __DIR__ . '/../rencana_tugas/service.php';

class CetakRpsService extends BaseService
{
    public function __construct(CetakRpsRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): CetakRpsRepository
    {
        /** @var CetakRpsRepository */
        return parent::repository();
    }

    public function getData(int $mkId, int $periodeId): array
    {
        $mk = $this->repository()->getMataKuliah($mkId);

        if (!$mk) {
            return $this->error('Mata Kuliah tidak ditemukan.');
        }

        $institusi = $this->repository()->getInstitutionProfile();
        $profilProdi = $this->repository()->getProfilProdi((int) $mk['unit_id']);
        $dosenList = $this->repository()->getDosenList($mkId, $periodeId);

        $pengembang = !empty($mk['dosen_pengembang_rps_id'])
            ? $this->repository()->getDosenById((int) $mk['dosen_pengembang_rps_id'])
            : null;

        $gkm = !empty($mk['gkm_dosen_id'])
            ? $this->repository()->getDosenById((int) $mk['gkm_dosen_id'])
            : null;

        $koordinator = null;
        $timTeaching = [];
        foreach ($dosenList as $d) {
            if ($d['peran'] === 'Koordinator') {
                $koordinator = $d;
            } else {
                $timTeaching[] = $d;
            }
        }

        return $this->success([
            'mk'               => $mk,
            'periode_id'       => $periodeId,
            'institusi'        => $institusi,
            'profil_prodi'     => $profilProdi,
            'pengembang'       => $pengembang,
            'gkm'              => $gkm,
            'koordinator'      => $koordinator,
            'tim_teaching'     => $timTeaching,
            'rps_list'         => $this->repository()->getRpsList($mkId, $periodeId),
            'jadwal_dosen'     => $this->repository()->getJadwalDosen($mkId, $periodeId),
            'rubrik_indikator' => $this->repository()->getRubrikIndikator($mkId, $periodeId),
            'mahasiswa_list'   => $this->repository()->getMahasiswaListByKurikulum((int) $mk['kurikulum_id']),
            'cpl_list'         => $this->repository()->getCplList($mkId),
            'cpmk_list'        => $this->repository()->getCpmkList($mkId),
            'sub_cpmk_list'    => $this->repository()->getSubCpmkList($mkId),
            'rencana_tugas_list' => $this->getRencanaTugasList($mkId, $periodeId),
        ]);
    }

    private function getRencanaTugasList(int $mkId, int $periodeId): array
    {
        global $conn;
        $rtRepo = new RencanaTugasRepository($conn);
        $rtService = new RencanaTugasService($rtRepo);
        $result = $rtService->getForMataKuliah($mkId, $periodeId);

        return $result['success'] ? $result['data'] : [];
    }
}