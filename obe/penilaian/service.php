<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class PenilaianService extends BaseService
{
    public function __construct(PenilaianRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): PenilaianRepository
    {
        /** @var PenilaianRepository */
        return parent::repository();
    }

    private function buildKomponenList(array $rawRows): array
    {
        $list = [];

        foreach ($rawRows as $row) {
            $reId = $row['rencana_evaluasi_id'] !== null ? (int) $row['rencana_evaluasi_id'] : 0;
            $bobot = $reId !== 0 ? (float) $row['komponen_bobot'] : (float) $row['rps_bobot_total'];
            $label = $reId !== 0 ? ($row['basis_evaluasi'] ?? 'Komponen') : 'Nilai TM';

            $list[] = [
                'rps_id'               => (int) $row['rps_id'],
                'rencana_evaluasi_id'  => $reId,
                'pertemuan'            => isset($row['pertemuan']) ? (int) $row['pertemuan'] : null,
                'label'                => $label,
                'bobot'                => $bobot,
                'cpmk_id'              => isset($row['cpmk_id']) && $row['cpmk_id'] ? (int) $row['cpmk_id'] : null,
                'cpmk_code'            => $row['cpmk_code'] ?? null,
                'sub_cpmk_id'          => isset($row['sub_cpmk_id']) && $row['sub_cpmk_id'] ? (int) $row['sub_cpmk_id'] : null,
                'sub_cpmk_code'        => $row['sub_cpmk_code'] ?? null,
                'cpl_id'               => isset($row['cpl_id']) && $row['cpl_id'] ? (int) $row['cpl_id'] : null,
                'cpl_code'             => $row['cpl_code'] ?? null,
            ];
        }

        return $list;
    }

    private function buildNilaiMap(array $nilaiRows): array
    {
        $map = [];
        foreach ($nilaiRows as $row) {
            $key = $row['rps_id'] . '_' . ((int) $row['rencana_evaluasi_id']) . '_' . $row['mahasiswa_id'];
            $map[$key] = (float) $row['nilai'];
        }

        return $map;
    }

    public function getGridData(int $mataKuliahId, int $kurikulumId, int $periodeId): array
    {
        $komponenRaw = $this->repository()->getKomponenListByMataKuliah($mataKuliahId, $periodeId);
        $mahasiswaList = $this->repository()->getMahasiswaListByKurikulum($kurikulumId);
        $nilaiRows = $this->repository()->getNilaiGrid($mataKuliahId, $periodeId);

        return $this->success([
            'komponen_list'  => $this->buildKomponenList($komponenRaw),
            'mahasiswa_list' => $mahasiswaList,
            'nilai_map'      => $this->buildNilaiMap($nilaiRows),
        ]);
    }

    public function saveNilaiBatch(int $periodeId, array $items): array
    {
        $savedCount = 0;

        foreach ($items as $item) {

            $rpsId = (int) ($item['rps_id'] ?? 0);
            $reId = (int) ($item['rencana_evaluasi_id'] ?? 0);
            $mahasiswaId = (int) ($item['mahasiswa_id'] ?? 0);
            $nilai = (float) ($item['nilai'] ?? 0);

            if ($rpsId <= 0 || $mahasiswaId <= 0) {
                continue;
            }

            if ($nilai < 0 || $nilai > 100) {
                continue;
            }

            $this->repository()->saveNilai($rpsId, $periodeId, $reId, $mahasiswaId, $nilai);
            $savedCount++;
        }

        return $this->success(['saved' => $savedCount], "Berhasil menyimpan {$savedCount} nilai.");
    }

    public function getLaporanCapaian(int $mataKuliahId, int $kurikulumId, int $periodeId): array
    {
        $komponenRaw = $this->repository()->getKomponenWithCplByMataKuliah($mataKuliahId, $periodeId);
        $mahasiswaList = $this->repository()->getMahasiswaListByKurikulum($kurikulumId);
        $nilaiRows = $this->repository()->getNilaiGrid($mataKuliahId, $periodeId);

        $komponenList = $this->buildKomponenList($komponenRaw);
        $nilaiMap = $this->buildNilaiMap($nilaiRows);

        // Kumpulkan daftar CPL unik yang terlibat di MK ini, urut berdasar kode
        $cplList = [];
        foreach ($komponenList as $k) {
            if ($k['cpl_id'] && !isset($cplList[$k['cpl_id']])) {
                $cplList[$k['cpl_id']] = $k['cpl_code'];
            }
        }
        asort($cplList);

        $hasil = [];

        foreach ($mahasiswaList as $mhs) {

            $nilaiAkhirMk = 0;
            $cplSumNilaiBobot = [];
            $cplSumBobot = [];

            foreach ($komponenList as $k) {

                $key = $k['rps_id'] . '_' . $k['rencana_evaluasi_id'] . '_' . $mhs['id'];
                $nilai = $nilaiMap[$key] ?? null;

                if ($nilai === null) {
                    continue;
                }

                $bobot = $k['bobot'];

                $nilaiAkhirMk += $nilai * $bobot / 100;

                if ($k['cpl_id']) {
                    $cplId = $k['cpl_id'];
                    $cplSumNilaiBobot[$cplId] = ($cplSumNilaiBobot[$cplId] ?? 0) + ($nilai * $bobot);
                    $cplSumBobot[$cplId] = ($cplSumBobot[$cplId] ?? 0) + $bobot;
                }
            }

            $ketercapaianCpl = [];
            foreach ($cplList as $cplId => $cplCode) {
                if (isset($cplSumBobot[$cplId]) && $cplSumBobot[$cplId] > 0) {
                    $ketercapaianCpl[$cplId] = round($cplSumNilaiBobot[$cplId] / $cplSumBobot[$cplId], 2);
                } else {
                    $ketercapaianCpl[$cplId] = null;
                }
            }

            $hasil[] = [
                'mahasiswa_id'      => $mhs['id'],
                'nim'               => $mhs['nim'],
                'nama'              => $mhs['nama'],
                'nilai_akhir_mk'    => round($nilaiAkhirMk, 2),
                'ketercapaian_cpl'  => $ketercapaianCpl,
            ];
        }

        return $this->success([
            'cpl_list' => $cplList,
            'mahasiswa' => $hasil,
        ]);
    }
}