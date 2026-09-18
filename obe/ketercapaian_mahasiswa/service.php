<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class KetercapaianMahasiswaService extends BaseService
{
    public function __construct(KetercapaianMahasiswaRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): KetercapaianMahasiswaRepository
    {
        /** @var KetercapaianMahasiswaRepository */
        return parent::repository();
    }

    public function getMahasiswaList(int $kurikulumId): array
    {
        return $this->success($this->repository()->getMahasiswaListByKurikulum($kurikulumId));
    }

    public function getLaporanMahasiswa(int $unitId, int $kurikulumId, int $mahasiswaId, int $periodeId): array
    {
        $mkList = $this->repository()->getMataKuliahListByKurikulum($kurikulumId);
        $cplList = $this->repository()->getCplListByUnit($unitId);
        $bobotRows = $this->repository()->getBobotKontribusiByKurikulum($kurikulumId);

        $bobotMap = [];
        foreach ($bobotRows as $row) {
            $key = $row['mata_kuliah_id'] . '_' . $row['cpl_id'];
            $bobotMap[$key] = (float) $row['bobot_persen'];
        }

        $mkCplScore = [];
        $mkNilaiAkhir = [];
        $mkDiambil = [];

        foreach ($mkList as $mk) {

            $komponenRaw = $this->repository()->getRpsWithCplByMataKuliah((int) $mk['id'], $periodeId);
            $nilaiRows = $this->repository()->getNilaiByMahasiswaAndMataKuliah($mahasiswaId, (int) $mk['id'], $periodeId);

            if (!$nilaiRows) {
                continue; // Mahasiswa belum ambil/belum ada nilai di MK ini
            }

            $mkDiambil[] = $mk;

            $nilaiMap = [];
            foreach ($nilaiRows as $n) {
                $key = $n['rps_id'] . '_' . ((int) $n['rencana_evaluasi_id']);
                $nilaiMap[$key] = (float) $n['nilai'];
            }

            $nilaiAkhirMk = 0;
            $cplSumNilaiBobot = [];
            $cplSumBobot = [];

            foreach ($komponenRaw as $row) {

                $reId = $row['rencana_evaluasi_id'] !== null ? (int) $row['rencana_evaluasi_id'] : 0;
                $bobot = $reId !== 0 ? (float) $row['komponen_bobot'] : (float) $row['rps_bobot_total'];

                $key = $row['rps_id'] . '_' . $reId;
                $nilai = $nilaiMap[$key] ?? null;

                if ($nilai === null) continue;

                $nilaiAkhirMk += $nilai * $bobot / 100;

                if ($row['cpl_id']) {
                    $cplId = $row['cpl_id'];
                    $cplSumNilaiBobot[$cplId] = ($cplSumNilaiBobot[$cplId] ?? 0) + ($nilai * $bobot);
                    $cplSumBobot[$cplId] = ($cplSumBobot[$cplId] ?? 0) + $bobot;
                }
            }

            $mkNilaiAkhir[$mk['id']] = round($nilaiAkhirMk, 2);

            foreach ($cplSumBobot as $cplId => $totalBobot) {
                if ($totalBobot > 0) {
                    $mkCplScore[$mk['id'] . '_' . $cplId] = round($cplSumNilaiBobot[$cplId] / $totalBobot, 2);
                }
            }
        }

        // Rekap tingkat Prodi utk Mahasiswa ini: Σ(Nilai Capaian CPL tiap MK × Bobot Kontribusi / 100)
        $cplResult = [];

        foreach ($cplList as $cpl) {

            $sumTertimbang = 0;
            $sumBobotDipakai = 0;
            $detailMk = [];

            foreach ($mkDiambil as $mk) {

                $keyScore = $mk['id'] . '_' . $cpl['id'];
                $keyBobot = $mk['id'] . '_' . $cpl['id'];

                if (!isset($mkCplScore[$keyScore]) || !isset($bobotMap[$keyBobot])) {
                    continue;
                }

                $skor = $mkCplScore[$keyScore];
                $bobot = $bobotMap[$keyBobot];

                $sumTertimbang += $skor * $bobot / 100;
                $sumBobotDipakai += $bobot;

                $detailMk[] = [
                    'mata_kuliah' => $mk['name'],
                    'skor'        => $skor,
                    'bobot'       => $bobot,
                ];
            }

            $skorAkhir = $sumBobotDipakai > 0 ? round($sumTertimbang, 2) : null;

            $cplResult[] = [
                'cpl_code'     => $cpl['code'],
                'ketercapaian' => $skorAkhir,
                'status'       => $skorAkhir === null ? null : ($skorAkhir >= 65 ? 'Memenuhi' : 'Belum Memenuhi'),
                'detail_mk'    => $detailMk,
            ];
        }

        return $this->success([
            'cpl_hasil'        => $cplResult,
            'cpl_list'         => $cplList,
            'mk_cpl_score'     => $mkCplScore,
            'mk_diambil'       => array_map(function ($mk) use ($mkNilaiAkhir) {
                return [
                    'id'           => $mk['id'],
                    'nama'         => $mk['name'],
                    'semester'     => $mk['semester'],
                    'nilai_akhir'  => $mkNilaiAkhir[$mk['id']] ?? null,
                ];
            }, $mkDiambil),
        ]);
    }

    public function getDetailPertemuan(int $mahasiswaId, int $mataKuliahId): array
    {
        return $this->success($this->repository()->getDetailNilaiPerTm($mahasiswaId, $mataKuliahId));
    }
}