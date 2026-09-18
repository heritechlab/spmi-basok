<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class BobotKontribusiService extends BaseService
{
    public function __construct(BobotKontribusiRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): BobotKontribusiRepository
    {
        /** @var BobotKontribusiRepository */
        return parent::repository();
    }

    public function getMatriksData(int $unitId, int $kurikulumId): array
    {
        $mkList = $this->repository()->getMataKuliahListByKurikulum($kurikulumId);
        $cplList = $this->repository()->getCplListByUnit($unitId);
        $bobotRows = $this->repository()->getBobotByKurikulum($kurikulumId);

        $bobotMap = [];
        foreach ($bobotRows as $row) {
            $key = $row['mata_kuliah_id'] . '_' . $row['cpl_id'];
            $bobotMap[$key] = (float) $row['bobot_persen'];
        }

        return $this->success([
            'mata_kuliah' => $mkList,
            'cpl'         => $cplList,
            'bobot_map'   => $bobotMap,
        ]);
    }

    public function saveBobotBatch(array $items): array
    {
        $savedCount = 0;

        foreach ($items as $item) {

            $mataKuliahId = (int) ($item['mata_kuliah_id'] ?? 0);
            $cplId = (int) ($item['cpl_id'] ?? 0);
            $bobot = (float) ($item['bobot'] ?? 0);

            if ($mataKuliahId <= 0 || $cplId <= 0 || $bobot < 0 || $bobot > 100) {
                continue;
            }

            $this->repository()->saveBobot($mataKuliahId, $cplId, $bobot);
            $savedCount++;
        }

        return $this->success(['saved' => $savedCount], "Berhasil menyimpan {$savedCount} bobot.");
    }

    public function getTingkat1(int $kurikulumId, int $periodeId): array
    {
        $rows = $this->repository()->getTingkat1ByKurikulum($kurikulumId, $periodeId);

        // Kumpulkan Total per Kolom (per CPL, dijumlah dari SEMUA Mata Kuliah)
        $totalPerCpl = [];
        foreach ($rows as $row) {
            $cplId = $row['cpl_id'];
            $totalPerCpl[$cplId] = ($totalPerCpl[$cplId] ?? 0) + (float) $row['total_bobot'];
        }

        // Normalisasi: nilai mentah / Total Kolom CPL itu * 100
        $result = [];
        foreach ($rows as $row) {
            $key = $row['mata_kuliah_id'] . '_' . $row['cpl_id'];
            $cplId = $row['cpl_id'];

            if ($totalPerCpl[$cplId] > 0) {
                $result[$key] = round(((float) $row['total_bobot'] / $totalPerCpl[$cplId]) * 100, 2);
            } else {
                $result[$key] = 0;
            }
        }

        return $this->success($result);
    }

        public function hitungOtomatis(int $unitId, int $kurikulumId, int $periodeId): array
    {
        $tingkat1 = $this->getTingkat1($kurikulumId, $periodeId);

        if (!$tingkat1['success']) {
            return $tingkat1;
        }

        // Hapus SEMUA data lama utk Kurikulum ini dulu, supaya tidak ada sisa nyangkut
        $this->repository()->deleteAllByKurikulum($kurikulumId);

        $savedCount = 0;

        foreach ($tingkat1['data'] as $key => $bobot) {

            [$mataKuliahId, $cplId] = explode('_', $key);

            if ((float) $bobot <= 0) {
                continue;
            }

            $this->repository()->saveBobot((int) $mataKuliahId, (int) $cplId, (float) $bobot);
            $savedCount++;
        }

        return $this->success(['saved' => $savedCount], "Berhasil menghitung ulang {$savedCount} nilai (data lama sudah dibersihkan).");
    }
        public function getTingkat1Detail(int $kurikulumId, int $periodeId): array
    {
        $rows = $this->repository()->getTingkat1DetailByKurikulum($kurikulumId, $periodeId);

        $totalPerCpl = [];
        foreach ($rows as $row) {
            $cplId = $row['cpl_id'];
            $totalPerCpl[$cplId] = ($totalPerCpl[$cplId] ?? 0) + (float) $row['total_bobot'];
        }

        foreach ($rows as &$row) {
            $cplId = $row['cpl_id'];
            $row['total_bobot'] = (float) $row['total_bobot'];
            $row['persen_normalisasi'] = $totalPerCpl[$cplId] > 0
                ? round(($row['total_bobot'] / $totalPerCpl[$cplId]) * 100, 2)
                : 0;
        }

        return $this->success($rows);
    }
}