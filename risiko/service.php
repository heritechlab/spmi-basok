<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class RisikoService extends BaseService
{
    public function __construct(RisikoRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): RisikoRepository
    {
        /** @var RisikoRepository */
        return parent::repository();
    }

    private const URUTAN_LEVEL = ['Rendah', 'Sedang', 'Tinggi', 'Ekstrem'];

    public static function hitungLevelDasar(int $likelihood, int $impact): string
    {
        $skor = $likelihood * $impact;

        if ($likelihood >= 4 && $impact >= 3) return 'Ekstrem';
        if ($likelihood >= 3 && $impact >= 3) return $likelihood == 4 ? 'Ekstrem' : 'Tinggi';
        if ($skor >= 12) return 'Ekstrem';
        if ($skor >= 8) return 'Tinggi';
        if ($skor >= 4) return 'Sedang';
        return 'Rendah';
    }

    /**
     * Sesuai Pasal 10 Peraturan Rektor UIN Alauddin: 4 kriteria (Likelihood, Impact,
     * Detectability, Control Readiness). Detectability/Control Readiness rendah (sulit
     * dideteksi / belum ada pengendalian) MENAIKKAN Level Risiko dasar 1 tingkat.
     */
    public static function hitungLevelRisiko(int $likelihood, int $impact, ?int $detectability = null, ?int $controlReadiness = null): string
    {
        $level = self::hitungLevelDasar($likelihood, $impact);
        $idx = array_search($level, self::URUTAN_LEVEL, true);

        $perluNaik = ($detectability !== null && $detectability <= 2) || ($controlReadiness !== null && $controlReadiness <= 2);

        if ($perluNaik && $idx < count(self::URUTAN_LEVEL) - 1) {
            $idx++;
        }

        return self::URUTAN_LEVEL[$idx];
    }

    public static function hitungDeadlineHari(string $levelRisiko): int
    {
        // Pasal 30 & 35: tenggat tindakan korektif per Level Risiko
        return match ($levelRisiko) {
            'Ekstrem', 'Tinggi' => 14,
            'Sedang' => 30,
            default => 60,
        };
    }

    public function getIdentifikasiData(int $unitId = 0): array
    {
        $manuals = $this->repository()->getQualityManualsGrouped();
        $statements = $this->repository()->getStandardStatements($unitId);
        $indicators = $this->repository()->getIndicators($unitId);
        $riskCounts = $this->repository()->getAllRiskCounts();

        $grouped = [];
        foreach ($manuals as $m) {
            $cat = $m['category'] ?: 'Lainnya';
            $grouped[$cat][] = $m;
        }

        return $this->success([
            'manuals_grouped' => $grouped,
            'statements' => $statements,
            'indicators' => $indicators,
            'risk_counts' => $riskCounts,
        ]);
    }

    public function tambahRisiko(int $unitId, string $sumberJenis, int $sumberId, string $deskripsi, ?string $kategori, ?int $createdBy): array
    {
        if (trim($deskripsi) === '') {
            return $this->error('Deskripsi Risiko wajib diisi.');
        }

        $id = $this->repository()->createRisk($unitId, $sumberJenis, $sumberId, trim($deskripsi), $kategori, $createdBy);

        return $this->success(['id' => $id], 'Risiko berhasil diidentifikasi.');
    }

    public function getAnalisisData(): array
    {
        $risks = $this->repository()->getRisksByStatus(['Teridentifikasi']);

        $matrix = array_fill(1, 4, array_fill(1, 4, 0));
        foreach ($this->repository()->getRisksByStatus(['Teranalisis', 'Termitigasi']) as $r) {
            if ($r['likelihood'] && $r['impact']) {
                $matrix[(int) $r['likelihood']][(int) $r['impact']]++;
            }
        }

        return $this->success(['risks' => $risks, 'matrix' => $matrix]);
    }

    public function analisisRisiko(int $riskId, int $likelihood, int $impact, ?int $detectability, ?int $controlReadiness, ?string $deskripsiDampak): array
    {
        if ($likelihood < 1 || $likelihood > 4 || $impact < 1 || $impact > 4) {
            return $this->error('Likelihood dan Impact harus antara 1-4.');
        }
        if ($detectability !== null && ($detectability < 1 || $detectability > 4)) {
            return $this->error('Detectability harus antara 1-4.');
        }
        if ($controlReadiness !== null && ($controlReadiness < 1 || $controlReadiness > 4)) {
            return $this->error('Control Readiness harus antara 1-4.');
        }

        $level = self::hitungLevelRisiko($likelihood, $impact, $detectability, $controlReadiness);
        $this->repository()->updateAnalysis($riskId, $likelihood, $impact, $detectability, $controlReadiness, $level, $deskripsiDampak);

        return $this->success(['level_risiko' => $level], 'Risiko berhasil dianalisis. Level: ' . $level);
    }

    /**
     * Sesuai Pasal 14: Prioritas Audit 3 tingkat, ditentukan dari komposisi Level
     * Risiko unit di Risk Register. Unit Prioritas Tinggi wajib diaudit min. 1x/tahun;
     * unit Prioritas Rendah boleh cukup audit dokumen (Pasal 14 ayat 3-4).
     */
    public function getPrioritasAudit(): array
    {
        $data = $this->repository()->getPrioritasAuditPerUnit();

        foreach ($data as &$u) {
            $ekstrem = (int) $u['jumlah_ekstrem'];
            $tinggi = (int) $u['jumlah_tinggi'];
            $sedang = (int) $u['jumlah_sedang'];

            if ($ekstrem >= 1 || $tinggi >= 2) {
                $u['prioritas'] = 'Tinggi';
                $u['ketentuan'] = 'Wajib diaudit minimal 1x/tahun (Pasal 14 ayat 3)';
            } elseif ($tinggi >= 1 || $sedang >= 2) {
                $u['prioritas'] = 'Sedang';
                $u['ketentuan'] = 'Direkomendasikan audit penuh';
            } elseif ((int) $u['total_risiko'] > 0) {
                $u['prioritas'] = 'Rendah';
                $u['ketentuan'] = 'Boleh audit dokumen saja (Pasal 14 ayat 4)';
            } else {
                $u['prioritas'] = 'Belum Ada Data';
                $u['ketentuan'] = 'Belum ada Risiko dianalisis untuk unit ini';
            }
        }
        unset($u);

        return $this->success($data);
    }

    /**
     * Pasal 32: Analisis Risiko Pascaaudit — langkah formal TERPISAH oleh LPM
     * setelah Laporan Audit resmi disampaikan. Mencakup: penyesuaian skor risiko,
     * pembaruan Risk Register, penetapan status baru, rekomendasi mitigasi lanjutan.
     */
    public function getFinishedAssignments(): array
    {
        return $this->success($this->repository()->getFinishedAssignments());
    }

    public function getPascaauditRisks(int $assignmentId): array
    {
        return $this->success($this->repository()->getRisksByAssignment($assignmentId));
    }

    public function simpanReviewPascaaudit(int $riskId, int $likelihood, int $impact, ?int $detectability, ?int $controlReadiness, string $rekomendasi, int $reviewedBy): array
    {
        if ($likelihood < 1 || $likelihood > 4 || $impact < 1 || $impact > 4) {
            return $this->error('Likelihood dan Impact harus antara 1-4.');
        }
        if (trim($rekomendasi) === '') {
            return $this->error('Rekomendasi Strategi Mitigasi Lanjutan wajib diisi (Pasal 32 ayat 2d).');
        }

        $level = self::hitungLevelRisiko($likelihood, $impact, $detectability, $controlReadiness);
        $this->repository()->updatePascaauditReview($riskId, $likelihood, $impact, $detectability, $controlReadiness, $level, trim($rekomendasi), $reviewedBy);

        return $this->success(['level_risiko' => $level], 'Analisis Risiko Pascaaudit tersimpan. Level terbaru: ' . $level);
    }

    public function sampaikanKeAuditee(int $assignmentId): array
    {
        $risks = $this->repository()->getRisksByAssignment($assignmentId);
        $belumDireview = array_filter($risks, fn($r) => (int) $r['reviewed_pascaaudit'] === 0);

        if (!empty($belumDireview)) {
            return $this->error('Masih ada ' . count($belumDireview) . ' Risiko yang belum direview. Selesaikan semua dulu sebelum menyampaikan ke Auditee.');
        }

        $this->repository()->markAssignmentDisampaikan($assignmentId);

        return $this->success([], 'Hasil Analisis Risiko Pascaaudit berhasil ditandai selesai & disampaikan ke Auditee (Pasal 32 ayat 3).');
    }

    public function getMitigasiData(): array
    {
        $risks = $this->repository()->getRisksByStatus(['Teranalisis', 'Termitigasi']);
        foreach ($risks as &$r) {
            $r['mitigasi'] = $this->repository()->getMitigationPlans((int) $r['id']);
        }
        unset($r);

        return $this->success(['risks' => $risks]);
    }

    public function tambahMitigasi(int $riskId, string $tindakan, ?string $akarMasalah, ?string $indikatorKeberhasilan, ?int $picUserId, ?string $targetTanggal): array
    {
        if (trim($tindakan) === '') {
            return $this->error('Tindakan Mitigasi wajib diisi.');
        }

        $risk = $this->repository()->findRiskById($riskId);
        $deadlineHari = self::hitungDeadlineHari($risk['level_risiko'] ?? 'Rendah');

        if (!$targetTanggal) {
            $targetTanggal = date('Y-m-d', strtotime("+{$deadlineHari} days"));
        }

        $id = $this->repository()->createMitigationPlan($riskId, trim($tindakan), $akarMasalah, $indikatorKeberhasilan, $picUserId, $targetTanggal, $deadlineHari);

        return $this->success(['id' => $id, 'deadline_hari' => $deadlineHari], 'Rencana Mitigasi berhasil ditambahkan. Tenggat: ' . $deadlineHari . ' hari kerja (sesuai Level Risiko).');
    }

    public function updateStatusEfektivitas(int $planId, string $status): array
    {
        if (!in_array($status, ['Belum Dinilai', 'Efektif', 'Sebagian Efektif', 'Tidak Efektif'], true)) {
            return $this->error('Status Efektivitas tidak valid.');
        }

        $this->repository()->updateStatusEfektivitas($planId, $status);

        return $this->success([], 'Status Efektivitas berhasil diperbarui.');
    }

    public function updateMitigasiStatus(int $planId, string $status): array
    {
        if (!in_array($status, ['Belum', 'Proses', 'Selesai'], true)) {
            return $this->error('Status tidak valid.');
        }

        $this->repository()->updateMitigationStatus($planId, $status);

        return $this->success([], 'Status berhasil diperbarui.');
    }

    public function getLinkOptions(int $riskId): array
    {
        return $this->success([
            'rtl_options' => $this->repository()->getRtlOptionsForRisk($riskId),
            'ptp_options' => $this->repository()->getPtpOptionsForRisk($riskId),
            'linked_rtl' => $this->repository()->getLinkedRtl($riskId),
            'linked_ptp' => $this->repository()->getLinkedPtp($riskId),
        ]);
    }

    public function tautkanRtl(int $riskId, int $rtlId): array
    {
        $this->repository()->linkToRtl($riskId, $rtlId);
        return $this->success([], 'Risiko berhasil ditautkan ke RTL.');
    }

    public function tautkanPtp(int $riskId, int $ptpId): array
    {
        $this->repository()->linkToPtp($riskId, $ptpId);
        return $this->success([], 'Risiko berhasil ditautkan ke PTP.');
    }

    public function tandaiTermitigasi(int $riskId): array
    {
        $this->repository()->updateStatus($riskId, 'Termitigasi');

        return $this->success([], 'Risiko ditandai Termitigasi.');
    }
}