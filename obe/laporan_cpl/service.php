<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class LaporanCplService extends BaseService
{
    public function __construct(LaporanCplRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): LaporanCplRepository
    {
        /** @var LaporanCplRepository */
        return parent::repository();
    }

    public function getLaporanProdi(int $unitId, int $periodeId): array
    {
        $mkList = $this->repository()->getMataKuliahListByUnit($unitId, $periodeId);
        $cplList = $this->repository()->getCplListByUnit($unitId);
        $bobotRows = $this->repository()->getBobotKontribusiByUnit($unitId);
        $mahasiswaList = $this->repository()->getMahasiswaListByUnit($unitId);

        $bobotMap = [];
        foreach ($bobotRows as $row) {
            $key = $row['mata_kuliah_id'] . '_' . $row['cpl_id'];
            $bobotMap[$key] = (float) $row['bobot_persen'];
        }

        // Hitung rata-rata kelas per MK per CPL (Tingkat 3 dirata-ratakan seluruh Mahasiswa)
        $mkCplScore = [];
        $mkDetail = [];

        foreach ($mkList as $mk) {

            $komponenRaw = $this->repository()->getRpsWithCplByMataKuliah((int) $mk['id'], $periodeId);
            $nilaiRows = $this->repository()->getNilaiByMataKuliah((int) $mk['id'], $periodeId);

            $nilaiMap = [];
            foreach ($nilaiRows as $n) {
                $key = $n['rps_id'] . '_' . ((int) $n['rencana_evaluasi_id']) . '_' . $n['mahasiswa_id'];
                $nilaiMap[$key] = (float) $n['nilai'];
            }

            $cplScoresAllMhs = []; // cpl_id => [skor mahasiswa1, skor mahasiswa2, ...]

            foreach ($mahasiswaList as $mhs) {

                $cplSumNilaiBobot = [];
                $cplSumBobot = [];

                foreach ($komponenRaw as $row) {

                    if (!$row['cpl_id']) continue;

                    $reId = $row['rencana_evaluasi_id'] !== null ? (int) $row['rencana_evaluasi_id'] : 0;
                    $bobot = $reId !== 0 ? (float) $row['komponen_bobot'] : (float) $row['rps_bobot_total'];

                    $key = $row['rps_id'] . '_' . $reId . '_' . $mhs['id'];
                    $nilai = $nilaiMap[$key] ?? null;

                    if ($nilai === null) continue;

                    $cplId = $row['cpl_id'];

                    $cplSumNilaiBobot[$cplId] = ($cplSumNilaiBobot[$cplId] ?? 0) + ($nilai * $bobot);
                    $cplSumBobot[$cplId] = ($cplSumBobot[$cplId] ?? 0) + $bobot;
                }

                foreach ($cplSumBobot as $cplId => $totalBobot) {
                    if ($totalBobot > 0) {
                        $skorMhs = $cplSumNilaiBobot[$cplId] / $totalBobot;
                        $cplScoresAllMhs[$cplId][] = $skorMhs;
                    }
                }
            }

            foreach ($cplScoresAllMhs as $cplId => $scores) {
                $avg = array_sum($scores) / count($scores);
                $mkCplScore[$mk['id'] . '_' . $cplId] = round($avg, 2);
            }

            $mkDetail[$mk['id']] = $mk;
        }

        // Tingkat 4: Ketercapaian CPL Prodi = Σ (Nilai Capaian CPL tiap MK × Bobot Kontribusi / 100)
        $cplProdiResult = [];

        foreach ($cplList as $cpl) {

            $sumTertimbang = 0;
            $sumBobotDipakai = 0;
            $kontribusiMk = [];

            foreach ($mkList as $mk) {

                $keyScore = $mk['id'] . '_' . $cpl['id'];
                $keyBobot = $mk['id'] . '_' . $cpl['id'];

                if (!isset($mkCplScore[$keyScore]) || !isset($bobotMap[$keyBobot])) {
                    continue;
                }

                $skor = $mkCplScore[$keyScore];
                $bobot = $bobotMap[$keyBobot];

                $sumTertimbang += $skor * $bobot / 100;
                $sumBobotDipakai += $bobot;

                $kontribusiMk[] = [
                    'mata_kuliah'  => $mk['name'],
                    'skor'         => $skor,
                    'bobot'        => $bobot,
                ];
            }

            $cplProdiResult[] = [
                'cpl_id'          => $cpl['id'],
                'cpl_code'        => $cpl['code'],
                'ketercapaian'    => $sumBobotDipakai > 0 ? round($sumTertimbang, 2) : null,
                'total_bobot_dipakai' => round($sumBobotDipakai, 2),
                'detail_mk'       => $kontribusiMk,
            ];
        }

        $periodeRow = $this->repository()->getPeriodeInfo($periodeId);
        $periodeLabel = $periodeRow ? $periodeRow['jenis_semester'] . ' ' . $periodeRow['tahun_ajaran'] : 'Program Studi';

        return $this->success([
            'cpl_hasil'        => $cplProdiResult,
            'jumlah_mk'        => count($mkList),
            'jumlah_mahasiswa' => count($mahasiswaList),
            'mk_list'          => $mkList,
            'cpl_list'         => $cplList,
            'mk_cpl_matrix'    => $mkCplScore,
            'bobot_map'        => $bobotMap,
            'periode_label'    => $periodeLabel,
        ]);
    }
}