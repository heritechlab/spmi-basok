<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../layouts/app.php';
require_once __DIR__ . '/../penilaian/repository.php';
require_once __DIR__ . '/../penilaian/service.php';

if (!Auth::isMahasiswa()) {
    die('<div style="padding:40px; font-family:sans-serif;">Halaman ini khusus untuk akun Mahasiswa.</div>');
}

$mahasiswaId = Auth::getMahasiswaId();

$stmtMhs = $conn->prepare("
    SELECT m.nim, m.nama, m.angkatan, m.kurikulum_id, u.name AS unit_name,
           d.name AS pa_name, d.gelar_depan AS pa_gelar_depan, d.gelar_belakang AS pa_gelar_belakang
    FROM obe_mahasiswa m
    JOIN obe_kurikulum k ON k.id = m.kurikulum_id
    JOIN units u ON u.id = k.unit_id
    LEFT JOIN obe_dosen d ON d.id = m.pa_dosen_id
    WHERE m.id = ? LIMIT 1
");
$stmtMhs->bind_param("i", $mahasiswaId);
$stmtMhs->execute();
$mhs = $stmtMhs->get_result()->fetch_assoc() ?: [];

$paLabel = $mhs['pa_name']
    ? trim(($mhs['pa_gelar_depan'] ?? '') . ' ' . $mhs['pa_name'] . ($mhs['pa_gelar_belakang'] ? ', ' . $mhs['pa_gelar_belakang'] : ''))
    : 'Belum ditentukan';

$periodeList = $conn->query("SELECT id, tahun_ajaran, jenis_semester FROM obe_periode_akademik ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$stmtMk = $conn->prepare("
    SELECT id, code, name, semester
    FROM obe_mata_kuliah
    WHERE kurikulum_id = ? AND is_active = 1
    ORDER BY semester ASC, name ASC
");
$stmtMk->bind_param("i", $mhs['kurikulum_id']);
$stmtMk->execute();
$mkList = $stmtMk->get_result()->fetch_all(MYSQLI_ASSOC);

function huruftMutu(float $nilai): string
{
    if ($nilai >= 80) return 'A';
    if ($nilai >= 75) return 'AB';
    if ($nilai >= 70) return 'B';
    if ($nilai >= 65) return 'BC';
    if ($nilai >= 56) return 'C';
    if ($nilai >= 40) return 'D';
    return 'E';
}

$penilaianRepo = new PenilaianRepository($conn);
$penilaianService = new PenilaianService($penilaianRepo);
$mkDetailAll = [];

foreach ($mkList as $mk) {
    foreach ($periodeList as $periode) {
        $gridResult = $penilaianService->getGridData((int) $mk['id'], (int) $mhs['kurikulum_id'], (int) $periode['id']);
        if (!$gridResult['success'] || empty($gridResult['data']['komponen_list'])) continue;

        $komponenRaw = $gridResult['data']['komponen_list'];
        $nilaiMap = $gridResult['data']['nilai_map'];

        $stmtStatus = $conn->prepare("
            SELECT rps_id, rencana_evaluasi_id, status_review
            FROM obe_penilaian
            WHERE mahasiswa_id = ? AND periode_id = ?
        ");
        $stmtStatus->bind_param("ii", $mahasiswaId, $periode['id']);
        $stmtStatus->execute();
        $statusMap = [];
        foreach ($stmtStatus->get_result()->fetch_all(MYSQLI_ASSOC) as $sr) {
            $statusMap[$sr['rps_id'] . '_' . $sr['rencana_evaluasi_id']] = $sr['status_review'];
        }

        $baris = [];
        $adaNilai = false;
        foreach ($komponenRaw as $k) {
            $key = $k['rps_id'] . '_' . $k['rencana_evaluasi_id'] . '_' . $mahasiswaId;
            $nilai = isset($nilaiMap[$key]) ? (float) $nilaiMap[$key] : null;
            if ($nilai !== null) $adaNilai = true;

            $statusKey = $k['rps_id'] . '_' . $k['rencana_evaluasi_id'];

            $baris[] = [
                'pertemuan'      => $k['pertemuan'],
                'label'          => $k['label'],
                'cpl_code'       => $k['cpl_code'],
                'bobot'          => $k['bobot'],
                'nilai'          => $nilai,
                'rps_id'         => $k['rps_id'],
                'rencana_evaluasi_id' => $k['rencana_evaluasi_id'],
                'status'         => $statusMap[$statusKey] ?? 'belum',
            ];
        }

        if ($adaNilai) {
            $sumNilaiBobot = 0; $sumBobot = 0;
            foreach ($baris as $b) {
                if ($b['nilai'] === null) continue;
                $sumNilaiBobot += $b['nilai'] * $b['bobot'];
                $sumBobot += $b['bobot'];
            }
            $nilaiAkhirMk = $sumBobot > 0 ? $sumNilaiBobot / $sumBobot : null;

            $mkDetailAll[] = [
                'mk_id'       => $mk['id'],
                'mk_code'     => $mk['code'],
                'mk_name'     => $mk['name'],
                'periode'     => $periode['jenis_semester'] . ' ' . $periode['tahun_ajaran'],
                'periode_id'  => $periode['id'],
                'nilai_akhir' => $nilaiAkhirMk,
                'baris'       => $baris,
            ];
            break;
        }
    }
}

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    #profilCapaianPage .profile-card {
        background: linear-gradient(135deg, #5b21b6 0%, #7c3aed 50%, #6d28d9 100%);
        border-radius: 20px; padding: 26px 30px; color: #fff; margin-bottom: 24px;
        display: flex; align-items: center; gap: 20px;
    }
    #profilCapaianPage .profile-avatar {
        width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,0.15);
        display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 800; flex-shrink: 0;
    }
    #profilCapaianPage .profile-name { font-size: 18px; font-weight: 800; }
    #profilCapaianPage .profile-info { font-size: 12px; opacity: .85; margin-top: 2px; }
    #profilCapaianPage .mk-detail-card { background: #fff; border: 1px solid #eceaf5; border-radius: 14px; margin-bottom: 14px; overflow: hidden; }
    #profilCapaianPage .mk-detail-header {
        padding: 14px 18px; background: #f9f8fc;
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
    }
    #profilCapaianPage .mk-detail-title { font-size: 13.5px; font-weight: 700; color: #14112b; }
    #profilCapaianPage .mk-detail-sub { font-size: 10.5px; color: #a39fb5; }
    #profilCapaianPage table.tm-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
    #profilCapaianPage table.tm-table th, #profilCapaianPage table.tm-table td { border: 1px solid #eceaf5; padding: 7px 10px; text-align: center; }
    #profilCapaianPage table.tm-table thead th { background: #faf9fd; color: #5b21b6; font-weight: 700; }
    #profilCapaianPage .nilai-rendah { background: #fef2f2 !important; color: #b42318; font-weight: 700; }
    #profilCapaianPage .btn-ajukan { font-size: 10px; padding: 3px 8px; }
</style>

<div class="container-fluid py-4" id="profilCapaianPage">

    <div class="profile-card">
        <div class="profile-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($mhs['nama'] ?? '?', 0, 1))) ?></div>
        <div>
            <div class="profile-name"><?= htmlspecialchars($mhs['nama'] ?? '-') ?></div>
            <div class="profile-info">
                NIM <?= htmlspecialchars($mhs['nim'] ?? '-') ?> &middot; <?= htmlspecialchars($mhs['unit_name'] ?? '-') ?> &middot; Angkatan <?= htmlspecialchars((string) ($mhs['angkatan'] ?? '-')) ?><br>
                PA (Pembimbing Akademik): <?= htmlspecialchars($paLabel) ?>
            </div>
        </div>
    </div>

    <div class="mb-3" style="font-size:15px; font-weight:700; color:#14112b;">Capaian Nilai Detail per Mata Kuliah</div>
    <p class="text-muted small mb-3">Nilai dengan latar merah adalah nilai di bawah 65 (BC) — Anda bisa mengajukan perbaikan langsung ke Dosen/PA lewat tombol di sampingnya.</p>

    <?php if (empty($mkDetailAll)): ?>
    <p class="text-muted">Belum ada nilai yang tercatat.</p>
    <?php endif; ?>

    <?php foreach ($mkDetailAll as $mkIdx => $mkData): ?>
    <div class="mk-detail-card">
        <div class="mk-detail-header">
            <div style="flex:1;">
                <div class="mk-detail-title"><?= htmlspecialchars($mkData['mk_code'] . ' - ' . $mkData['mk_name']) ?></div>
                <div class="mk-detail-sub"><?= htmlspecialchars($mkData['periode']) ?></div>
                <div class="mt-1">
                    <span class="badge" style="background:#ecfdf9; color:#0d9488; font-size:14px; padding:6px 14px; font-weight:800;">
                        Nilai Akhir MK: <?= $mkData['nilai_akhir'] !== null ? number_format($mkData['nilai_akhir'], 2) : '-' ?>
                    </span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success btn-setuju-semua-mk"
                    data-mk-id="<?= $mkData['mk_id'] ?>" data-periode-id="<?= $mkData['periode_id'] ?>">
                    Setuju Semua
                </button>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#mkBody<?= $mkIdx ?>">
                    <i class="bi bi-eye-fill"></i> Lihat Penilaian
                </button>
            </div>
        </div>
        <div class="collapse" id="mkBody<?= $mkIdx ?>">
            <table class="tm-table mb-0">
                <thead>
                    <tr>
                        <th>TM</th>
                        <th>Basis Evaluasi</th>
                        <th>CPL</th>
                        <th>Bobot</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mkData['baris'] as $b): ?>
                    <?php $rendah = $b['nilai'] !== null && $b['nilai'] < 65; ?>
                    <tr>
                        <td>TM-<?= $b['pertemuan'] ?></td>
                        <td><?= htmlspecialchars($b['label'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($b['cpl_code'] ?: '-') ?></td>
                        <td><?= number_format($b['bobot'], 1) ?>%</td>
                        <td class="<?= $rendah ? 'nilai-rendah' : '' ?>"><?= $b['nilai'] !== null ? number_format($b['nilai'], 0) : '-' ?></td>
                        <td>
                            <?php if ($b['status'] === 'setuju'): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Disetujui</span>
                            <?php elseif ($b['status'] === 'diajukan'): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Menunggu Tanggapan</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Belum Ditinjau</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b['nilai'] !== null && $b['status'] === 'belum'): ?>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-outline-success btn-ajukan btn-setuju-nilai"
                                    data-rps="<?= $b['rps_id'] ?>" data-re="<?= $b['rencana_evaluasi_id'] ?>">
                                    Setuju
                                </button>
                                <?php if ($rendah): ?>
                                <button type="button" class="btn btn-outline-danger btn-ajukan btn-ajukan-perbaikan"
                                    data-mk="<?= htmlspecialchars($mkData['mk_name']) ?>"
                                    data-tm="TM-<?= $b['pertemuan'] ?><?= in_array($b['label'], ['UTS', 'UAS'], true) ? ' (' . $b['label'] . ')' : '' ?>"
                                    data-nilai="<?= $b['nilai'] !== null ? number_format($b['nilai'], 0) : '-' ?>"
                                    data-rps="<?= $b['rps_id'] ?>" data-re="<?= $b['rencana_evaluasi_id'] ?>">
                                    Ajukan Perbaikan
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

  const mahasiswaId = <?= (int) $mahasiswaId ?>;
  const diskusiApi = SIQUA.BASE_URL + "obe/diskusi_cpl/api.php";

  $(document).on("click", ".btn-setuju-semua-mk", function (e) {
    e.stopPropagation();
    const mkId = $(this).data("mk-id");
    const periodeId = $(this).data("periode-id");

    Swal.fire({
      icon: "question",
      title: "Setujui semua nilai di MK ini?",
      text: "Semua nilai yang belum ditinjau di Mata Kuliah ini akan ditandai Disetujui sekaligus.",
      showCancelButton: true,
      confirmButtonText: "Ya, Setujui Semua",
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(diskusiApi + "?action=setuju_semua_mk", {
        mahasiswa_id: mahasiswaId, mata_kuliah_id: mkId, periode_id: periodeId,
      }, function (res) {
        if (res.success) {
          location.reload();
        } else {
          Swal.fire("Gagal", res.message, "error");
        }
      }, "json");
    });
  });

  $(document).on("click", ".btn-setuju-nilai", function () {
    const $btn = $(this);
    const rps = $btn.data("rps");
    const re = $btn.data("re");

    Swal.fire({
      icon: "question",
      title: "Setujui nilai ini?",
      text: "Anda menyatakan sudah melihat dan menerima nilai ini apa adanya.",
      showCancelButton: true,
      confirmButtonText: "Ya, Setuju",
    }).then(function (result) {
      if (!result.isConfirmed) return;

      $.post(diskusiApi + "?action=update_status_nilai", {
        mahasiswa_id: mahasiswaId, rps_id: rps, rencana_evaluasi_id: re, status: "setuju",
      }, function (res) {
        if (res.success) {
          location.reload();
        } else {
          Swal.fire("Gagal", res.message, "error");
        }
      }, "json");
    });
  });

  $(document).on("click", ".btn-ajukan-perbaikan", function () {
    const mk = $(this).data("mk");
    const tm = $(this).data("tm");
    const nilai = $(this).data("nilai");
    const rps = $(this).data("rps");
    const re = $(this).data("re");
    const defaultPesan = `Mohon peninjauan ulang nilai saya pada ${mk}, ${tm} (nilai saat ini: ${nilai}). Mohon arahan lebih lanjut dari Dosen/PA.`;

    Swal.fire({
      title: "Ajukan Perbaikan Nilai",
      input: "textarea",
      inputValue: defaultPesan,
      inputAttributes: { style: "font-size:13px;" },
      showCancelButton: true,
      confirmButtonText: "Kirim ke Dosen/PA",
      cancelButtonText: "Batal",
    }).then(function (result) {
      if (!result.isConfirmed || !result.value.trim()) return;

      $.post(diskusiApi + "?action=create", { mahasiswa_id: mahasiswaId, pesan: result.value.trim() }, function () {
        $.post(diskusiApi + "?action=update_status_nilai", {
          mahasiswa_id: mahasiswaId, rps_id: rps, rencana_evaluasi_id: re, status: "diajukan",
        });
      });

      $.post(diskusiApi + "?action=create", { mahasiswa_id: mahasiswaId, pesan: result.value.trim() }, function (res) {
        if (res.success) {
          Swal.fire({
            icon: "success",
            title: "Terkirim",
            text: "Pengajuan Anda sudah masuk ke ruang Diskusi & Umpan Balik CPL.",
            confirmButtonText: "Buka Diskusi",
            showCancelButton: true,
            cancelButtonText: "Tutup",
          }).then(function (r2) {
            if (r2.isConfirmed) {
              window.location.href = SIQUA.BASE_URL + "obe/ketercapaian_mahasiswa/diskusi_saya.php";
            }
          });
        } else {
          Swal.fire("Gagal", res.message, "error");
        }
      }, "json");
    });
  });

});
</script>