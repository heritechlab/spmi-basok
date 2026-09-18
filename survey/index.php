<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../master/institution/repository.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../master/unit/repository.php';

$institutionRepo = new InstitutionRepository($conn);
$profile = $institutionRepo->getProfile();

$repository = new SurveyRepository($conn);
$service    = new SurveyService($repository);

$slug = trim($_GET['type'] ?? '');

$typeResult = $service->getTypeBySlug($slug);

if (!$typeResult['success']) {
    die('<p style="font-family: sans-serif; padding: 40px; text-align:center;">Jenis survey tidak ditemukan. Pastikan tautan yang Anda buka benar.</p>');
}

$type = $typeResult['data'];
$nimPengisiMahasiswa = '';
if ($slug === 'mahasiswa' && !empty($_SESSION['mahasiswa_id'])) {
    $stmtNimSurvey = $conn->prepare("SELECT nim FROM obe_mahasiswa WHERE id = ? LIMIT 1");
    $stmtNimSurvey->bind_param("i", $_SESSION['mahasiswa_id']);
    $stmtNimSurvey->execute();
    $rowNimSurvey = $stmtNimSurvey->get_result()->fetch_assoc();
    $nimPengisiMahasiswa = $rowNimSurvey['nim'] ?? '';
}
$typeId = (int) $type['id'];

$surveyYear = (int) ($_GET['tahun'] ?? 0);
if ($surveyYear <= 0) {
    $surveyYear = (int) date('Y');
}

$isLayananBased = $service->isLayananBased($typeId);
$requiresIdentity = (int) ($type['requires_identity'] ?? 0) === 1;
$scaleConfig = $service->getScaleConfig($typeId);
$scaleMax = $scaleConfig['max'];
$scaleLabels = $scaleConfig['labels'];

if ($isLayananBased) {
    $layananForm = $service->getLayananForm($typeId)['data'];
    $categories = [];
} else {
    $categories = $service->getForm($typeId)['data'];
    $layananForm = [];
}

$units = $service->getProdiUnits()['data'];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($type['name']) ?> - <?= htmlspecialchars($profile['institution_name'] ?? 'SIQUA') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            background: #f7f6fb;
            margin: 0;
        }

        .hero {
            position: relative;
            background: linear-gradient(135deg, #2e0854 0%, #5b21b6 45%, #7c3aed 100%);
            color: #fff;
            padding: 60px 20px 100px;
            overflow: hidden;
            text-align: center;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: -120px;
            right: -100px;
            width: 380px;
            height: 380px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
        }

        .hero::after {
            content: "";
            position: absolute;
            bottom: -140px;
            left: -80px;
            width: 320px;
            height: 320px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 620px;
            margin: 0 auto;
        }

        .hero-logo {
            width: 84px;
            height: 84px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(6px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .hero-logo img { width: 60px; height: 60px; object-fit: contain; }

        .hero-eyebrow {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: #d6bbfb;
            margin-bottom: 10px;
        }

        .hero-title {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .hero-subtitle {
            font-size: 14.5px;
            color: rgba(255,255,255,0.82);
            line-height: 1.6;
        }

        .form-wrap {
            max-width: 720px;
            margin: -60px auto 60px;
            position: relative;
            z-index: 3;
            padding: 0 20px;
        }

        .form-card {
            background: #fff;
            border-radius: 22px;
            padding: 30px;
            box-shadow: 0 25px 60px rgba(46, 8, 84, 0.15);
        }

        .form-card label.section-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #6b6478;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            display: block;
        }

        .form-select, .form-control {
            border-radius: 10px;
            border: 1.5px solid #ece8f7;
            font-size: 13.5px;
            padding: 10px 12px;
        }

        .form-select:focus, .form-control:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
        }

        /* ===== LAYANAN (level teratas, khusus Mahasiswa) ===== */

        .layanan-block {
            margin-top: 30px;
            padding-top: 24px;
            border-top: 2px solid #f0edf9;
        }

.layanan-head {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #f3effc, #eef4ff);
            padding: 14px 18px;
            border-radius: 14px;
        }

        .layanan-head-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex: 0 0 auto;
            box-shadow: 0 8px 18px rgba(91,33,182,0.25);
        }

        .layanan-head-title {
            font-size: 17px;
            font-weight: 800;
            color: #2b2438;
        }

        /* ===== ASPEK (level tengah) ===== */

        .category-block {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #f0edf9;
        }

.category-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #3a3348;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #f3effc, #eef4ff);
            padding: 10px 14px;
            border-radius: 12px;
            border-left: 4px solid #7c3aed;
        }

        .category-title i {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: linear-gradient(135deg, #7c3aed, #2563eb);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex: 0 0 auto;
            box-shadow: 0 4px 10px rgba(124,58,237,0.25);
        }

        /* ===== PERTANYAAN ===== */

        .question-item {
            margin-bottom: 18px;
        }

        .question-text {
            font-size: 13.5px;
            color: #3a3348;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .likert-scale {
            display: flex;
            gap: 8px;
        }

        .likert-option {
            flex: 1;
            text-align: center;
        }

        .likert-option input {
            display: none;
        }

        .likert-option label {
            display: block;
            padding: 10px 4px;
            border-radius: 10px;
            border: 1.5px solid #ece8f7;
            font-size: 11px;
            font-weight: 600;
            color: #9b96a8;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.3;
        }

        .likert-option input:checked + label {
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            border-color: #5b21b6;
            color: #fff;
        }

        .likert-caption {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #b4aec2;
            margin-top: 4px;
            padding: 0 2px;
        }

        .btn-submit-premium {
            background: linear-gradient(135deg, #5b21b6, #7c3aed);
            border: none;
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14.5px;
            padding: 13px 0;
            width: 100%;
            margin-top: 28px;
            box-shadow: 0 12px 24px rgba(91,33,182,0.25);
        }

        .btn-submit-premium:hover { opacity: 0.92; color: #fff; }

        .public-footer {
            text-align: center;
            padding: 0 20px 40px;
            color: #a8a2b8;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="hero">
        <div class="hero-content">
            <div class="hero-logo">
                <?php if (!empty($profile['logo'])): ?>
                    <img src="<?= BASE_URL . htmlspecialchars($profile['logo']) ?>" alt="Logo">
                <?php else: ?>
                    <i class="bi bi-emoji-smile" style="font-size:32px;"></i>
                <?php endif; ?>
            </div>
            <div class="hero-eyebrow">Survey Kepuasan &mdash; Tahun <?= $surveyYear ?></div>
            <div class="hero-title"><?= htmlspecialchars($type['name']) ?></div>
            <div class="hero-subtitle">
                <?= htmlspecialchars($profile['institution_name'] ?? '') ?> &mdash;
                Masukan Anda sangat berarti bagi kami untuk terus meningkatkan kualitas layanan.
            </div>
        </div>
    </div>

    <div class="form-wrap">

        <?php $hasContent = $isLayananBased ? !empty($layananForm) : !empty($categories); ?>

        <?php if (!$hasContent): ?>

            <div class="form-card text-center">
                <i class="bi bi-hourglass-split" style="font-size:44px; color:#c4bde0;"></i>
                <h5 class="fw-bold mt-3 mb-2">Survey Belum Tersedia</h5>
                <p class="text-muted mb-0">Pertanyaan untuk survey ini belum disiapkan. Silakan hubungi Lembaga Penjaminan Mutu.</p>
            </div>

        <?php else: ?>

        <div id="surveySuccessBox" style="display:none;" class="form-card text-center">
            <i class="bi bi-check-circle-fill" style="font-size:48px; color:#22c55e;"></i>
            <h4 class="fw-bold mt-3 mb-2">Terima Kasih!</h4>
            <p class="text-muted mb-0">Survey Anda telah berhasil dikirim. Masukan Anda sangat berarti bagi kami.</p>
            <?php if ($slug === 'mahasiswa'): ?>
            <a href="<?= BASE_URL ?>auth/survey_gate.php" class="btn btn-primary mt-3">
                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Survey
            </a>
            <?php endif; ?>
        </div>
<form id="surveyForm" class="form-card">
<?php if ($slug === 'mahasiswa'): ?>
<input type="hidden" name="nim_pengisi" value="<?= htmlspecialchars($nimPengisiMahasiswa) ?>">
<?php endif; ?>

            <input type="hidden" name="type_id" value="<?= (int) $type['id'] ?>">
            <input type="hidden" name="survey_year" value="<?= $surveyYear ?>">

<?php if ($isLayananBased): ?>

            <label class="section-label">Program Studi <span style="color:#dc2626;">*</span></label>
            <select class="form-select" name="unit_id" id="unit_id" required>
                <option value="">-- Pilih Program Studi Anda --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>

<?php elseif ($requiresIdentity && $type['slug'] === 'pengguna'): ?>

            <label class="section-label">Identitas Pengisi (Pengguna Lulusan)</label>

            <div class="mb-2">
                <label class="form-label small mb-1">1. Nama Instansi/Perusahaan/Lembaga</label>
                <input type="text" class="form-control" name="nama_instansi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">2. Nama Pimpinan Instansi/Perusahaan/Lembaga</label>
                <input type="text" class="form-control" name="nama_pengisi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">3. Alamat Email</label>
                <input type="email" class="form-control" name="email" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">4. No. Handphone/Telephone</label>
                <input type="text" class="form-control" name="no_hp" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">5. Asal Bidang Ilmu Lulusan yang Dinilai</label>
                <select class="form-select" name="unit_id" id="unit_id" required>
                    <option value="">-- Pilih Program Studi --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">6. Nama atau Jumlah Lulusan yang Bekerja di Instansi/Perusahaan/Lembaga Bapak/Ibu</label>
                <input type="text" class="form-control" name="jumlah_lulusan" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">7. Rata-rata Masa Kerja Lulusan yang Bekerja di Instansi/Perusahaan/Lembaga Bapak/Ibu</label>
                <input type="text" class="form-control" name="masa_kerja" placeholder="Contoh: 2 tahun 3 bulan" required>
            </div>

<?php elseif ($requiresIdentity && $type['slug'] === 'mitra'): ?>

            <label class="section-label">Identitas Mitra</label>

            <div class="mb-2">
                <label class="form-label small mb-1">Nama Instansi/Lembaga Mitra</label>
                <input type="text" class="form-control" name="nama_instansi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Bidang/Bentuk Kerjasama</label>
                <input type="text" class="form-control" name="bidang_kerjasama" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Nama Pengisi</label>
                <input type="text" class="form-control" name="nama_pengisi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Jabatan</label>
                <input type="text" class="form-control" name="jabatan" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Lama Kerjasama (sejak tahun)</label>
                <input type="text" class="form-control" name="lama_kerjasama" placeholder="Contoh: 2019" required>
            </div>

<?php elseif ($requiresIdentity && $type['slug'] === 'mitra_penelitian'): ?>

            <label class="section-label">Identitas Mitra Penelitian</label>

            <div class="mb-2">
                <label class="form-label small mb-1">Nama Instansi/Lembaga Mitra</label>
                <input type="text" class="form-control" name="nama_instansi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Bidang/Jenis Penelitian Kerjasama</label>
                <input type="text" class="form-control" name="bidang_kerjasama" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Nama Pengisi</label>
                <input type="text" class="form-control" name="nama_pengisi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Jabatan</label>
                <input type="text" class="form-control" name="jabatan" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Lama Kerjasama Penelitian (sejak tahun)</label>
                <input type="text" class="form-control" name="lama_kerjasama" placeholder="Contoh: 2021" required>
            </div>

            <?php elseif ($requiresIdentity && $type['slug'] === 'mitra_pkm'): ?>

            <label class="section-label">Identitas Mitra Pengabdian Masyarakat</label>

            <div class="mb-2">
                <label class="form-label small mb-1">Nama Instansi/Lembaga Mitra</label>
                <input type="text" class="form-control" name="nama_instansi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Bidang/Jenis Pengabdian Masyarakat Kerjasama</label>
                <input type="text" class="form-control" name="bidang_kerjasama" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Nama Pengisi</label>
                <input type="text" class="form-control" name="nama_pengisi" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Jabatan</label>
                <input type="text" class="form-control" name="jabatan" required>
            </div>

            <div class="mb-2">
                <label class="form-label small mb-1">Lama Kerjasama Pengabdian Masyarakat (sejak tahun)</label>
                <input type="text" class="form-control" name="lama_kerjasama" placeholder="Contoh: 2021" required>
            </div>

            <?php else: ?>

            <label class="section-label">Unit Kerja / Program Studi (Opsional)</label>
            <select class="form-select" name="unit_id" id="unit_id">
                <option value="">-- Tidak Perlu Diisi --</option>
                <?php foreach ($units as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['code'] . ' - ' . $u['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <?php endif; ?>

            <?php
                /*
                |--------------------------------------------------------------------------
                | Fungsi cetak 1 blok pertanyaan (dipakai di kedua struktur)
                |--------------------------------------------------------------------------
                */
                function renderQuestionBlock(array $q, int $scaleMax, array $scaleLabels): void
                {
                    ?>
                    <div class="question-item">
                        <div class="question-text"><?= htmlspecialchars($q['question_text']) ?></div>

                        <div class="likert-scale">
                            <?php for ($i = 1; $i <= $scaleMax; $i++): ?>
                                <?php $optLabel = $scaleLabels[$i - 1] ?? (string) $i; ?>
                                <div class="likert-option">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" id="q<?= $q['id'] ?>_<?= $i ?>" value="<?= $i ?>" required>
                                    <label for="q<?= $q['id'] ?>_<?= $i ?>"><?= htmlspecialchars($optLabel) ?></label>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <?php if (empty($scaleLabels)): ?>
                        <div class="likert-caption">
                            <span>Sangat Tidak Puas</span>
                            <span>Sangat Puas</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php
                }
            ?>

            <?php if ($isLayananBased): ?>

                <?php $layananIcons = ['bi-person-workspace', 'bi-people-fill', 'bi-briefcase-fill', 'bi-building-gear']; ?>

                <?php foreach ($layananForm as $li => $layanan): ?>

                    <div class="layanan-block">

                        <div class="layanan-head">
                            <div class="layanan-head-icon"><i class="bi <?= $layananIcons[$li % 4] ?>"></i></div>
                            <div class="layanan-head-title">Layanan <?= htmlspecialchars($layanan['name']) ?></div>
                        </div>

                        <?php foreach ($layanan['aspek'] as $aspek): ?>

                            <div class="category-block">

                                <div class="category-title">
                                    <i class="bi bi-check2-square"></i>
                                    <?= htmlspecialchars($aspek['name']) ?>
                                </div>

                                <?php foreach ($aspek['questions'] as $q): ?>
                                    <?php renderQuestionBlock($q, $scaleMax, $scaleLabels); ?>
                                <?php endforeach; ?>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <?php foreach ($categories as $cat): ?>

                    <div class="category-block">

                        <div class="category-title">
                            <i class="bi bi-star-fill"></i>
                            <?= htmlspecialchars($cat['name']) ?>
                        </div>

                        <?php foreach ($cat['questions'] as $q): ?>
                            <?php renderQuestionBlock($q, $scaleMax, $scaleLabels); ?>
                        <?php endforeach; ?>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

            <div class="category-block">
                <label class="section-label">Saran &amp; Masukan (Opsional)</label>
                <textarea class="form-control" name="saran" rows="4" placeholder="Tuliskan saran atau masukan Anda untuk perbaikan layanan kami..."></textarea>
            </div>

            <button type="submit" class="btn-submit-premium" id="btnSubmitSurvey">
                <i class="bi bi-send-fill"></i> Kirim Survey
            </button>

        </form>

        <?php endif; ?>

    </div>

    <div class="public-footer">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($profile['institution_name'] ?? '') ?> &mdash; Sistem Informasi Audit Mutu Internal (SIQUA)
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function () {
            $("#surveyForm").on("submit", function (e) {
                e.preventDefault();

                const formData = $(this).serialize();
                const $btn = $("#btnSubmitSurvey");

                $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Mengirim...');

                $.ajax({
                    url: "<?= BASE_URL ?>survey/api.php?action=submit",
                    type: "POST",
                    dataType: "json",
                    data: formData,
                    success: function (response) {
                        if (!response.success) {
                            alert(response.message);
                            $btn.prop("disabled", false).html('<i class="bi bi-send-fill"></i> Kirim Survey');
                            return;
                        }

                        $("#surveyForm").hide();
                        $("#surveySuccessBox").fadeIn();
                        window.scrollTo({ top: 0, behavior: "smooth" });
                    },
                    error: function () {
                        alert("Terjadi kesalahan pada server. Silakan coba lagi.");
                        $btn.prop("disabled", false).html('<i class="bi bi-send-fill"></i> Kirim Survey');
                    },
                });
            });
        });
    </script>

</body>
</html>