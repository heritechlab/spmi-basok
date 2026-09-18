<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class PelaporanSiakadService extends BaseService
{
    private const ASPEK_LIST = ['SIKAP', 'PENGETAHUAN', 'KETERAMPILAN UMUM', 'KETERAMPILAN KHUSUS'];

    public function __construct(PelaporanSiakadRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): PelaporanSiakadRepository
    {
        /** @var PelaporanSiakadRepository */
        return parent::repository();
    }

    public function getMataKuliahList(int $kurikulumId): array
    {
        return $this->success($this->repository()->getMataKuliahListByKurikulum($kurikulumId));
    }

    public function getLaporan(int $mataKuliahId, int $kurikulumId): array
    {
        $mahasiswaList = $this->repository()->getMahasiswaListByKurikulum($kurikulumId);
        $komponenRaw = $this->repository()->getRpsWithKomponenByMataKuliah($mataKuliahId);
        $nilaiRows = $this->repository()->getNilaiByMataKuliah($mataKuliahId);

        $nilaiMap = [];
        foreach ($nilaiRows as $n) {
            $key = $n['rps_id'] . '_' . ((int) $n['rencana_evaluasi_id']) . '_' . $n['mahasiswa_id'];
            $nilaiMap[$key] = (float) $n['nilai'];
        }

        $hasil = [];

        // Bobot komposisi Nilai Akhir SIAKAD - bisa disesuaikan Prodi kalau perlu
        $bobotAspek = [
            'SIKAP'                => 0.20,
            'PENGETAHUAN'          => 0.40,
            'KETERAMPILAN KHUSUS'  => 0.20,
            'KETERAMPILAN UMUM'    => 0.20,
        ];

        foreach ($mahasiswaList as $mhs) {

            $aspekSumNilaiBobot = array_fill_keys(self::ASPEK_LIST, 0.0);
            $aspekSumBobot = array_fill_keys(self::ASPEK_LIST, 0.0);

            foreach ($komponenRaw as $row) {

                $reId = $row['rencana_evaluasi_id'] !== null ? (int) $row['rencana_evaluasi_id'] : 0;
                $bobot = $reId !== 0 ? (float) $row['komponen_bobot'] : (float) $row['rps_bobot_total'];

                $key = $row['rps_id'] . '_' . $reId . '_' . $mhs['id'];
                $nilai = $nilaiMap[$key] ?? null;

                if ($nilai === null) continue;

                $komponenList = $row['komponen_siakad'] ? array_map('trim', explode(',', $row['komponen_siakad'])) : [];

                foreach ($komponenList as $aspek) {
                    if (in_array($aspek, self::ASPEK_LIST, true)) {
                        $aspekSumNilaiBobot[$aspek] += $nilai * $bobot;
                        $aspekSumBobot[$aspek] += $bobot;
                    }
                }
            }

            $aspekResult = [];
            foreach (self::ASPEK_LIST as $aspek) {
                $aspekResult[$aspek] = $aspekSumBobot[$aspek] > 0
                    ? round($aspekSumNilaiBobot[$aspek] / $aspekSumBobot[$aspek], 2)
                    : null;
            }

            // Nilai Akhir = gabungan berbobot dari 4 Aspek (hanya Aspek yg ADA datanya yg dihitung, dinormalisasi)
            $totalTertimbang = 0;
            $totalBobotDipakai = 0;

            foreach ($bobotAspek as $aspek => $bobot) {
                if ($aspekResult[$aspek] !== null) {
                    $totalTertimbang += $aspekResult[$aspek] * $bobot;
                    $totalBobotDipakai += $bobot;
                }
            }

            $nilaiAkhir = $totalBobotDipakai > 0 ? round($totalTertimbang / $totalBobotDipakai, 2) : null;

            $hasil[] = [
                'mahasiswa_id' => $mhs['id'],
                'nim'          => $mhs['nim'],
                'nama'         => $mhs['nama'],
                'sikap'        => $aspekResult['SIKAP'],
                'pengetahuan'  => $aspekResult['PENGETAHUAN'],
                'ku'           => $aspekResult['KETERAMPILAN UMUM'],
                'kk'           => $aspekResult['KETERAMPILAN KHUSUS'],
                'total'        => $nilaiAkhir,
            ];
        }

        return $this->success($hasil);
    }
}