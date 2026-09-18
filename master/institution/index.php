<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../core/Auth.php';
$pageTitle = 'Profil Institusi';

$pageCss = 'assets/css/institution.css';

$pageScript = 'assets/js/institution.js';

$repository = new InstitutionRepository($conn);

$service = new InstitutionService($repository);

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid">

    <!-- ===================================================== -->
    <!-- PAGE HEADER -->
    <!-- ===================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="bi bi-building text-primary"></i>

                Profil Institusi

            </h3>

            <p class="text-muted mb-0">

                Pengaturan identitas institusi yang digunakan pada seluruh modul SIQUA.

            </p>

        </div>

        <button

            type="button"

            class="btn btn-primary <?= Auth::canManage() ? '' : 'btn-locked' ?>"

            id="btnSaveInstitution"

            <?= Auth::canManage() ? '' : 'title="Anda tidak memiliki akses untuk mengubah data"' ?>>

            <i class="bi bi-floppy"></i>

            Simpan Perubahan

        </button>

    </div>

    <!-- ===================================================== -->
    <!-- INFORMATION -->
    <!-- ===================================================== -->

    <div class="alert alert-primary border-0 shadow-sm">

        <div class="row align-items-center">

            <div class="col-auto">

                <i class="bi bi-info-circle-fill fs-2"></i>

            </div>

            <div class="col">

                <strong>

                    Informasi

                </strong>

                <div>

                    Profil institusi digunakan pada Dashboard,
                    Audit Mutu Internal, Berita Acara,
                    Laporan Audit dan seluruh dokumen SIQUA.

                </div>

            </div>

        </div>

    </div>

        <form

        id="institutionForm"

        autocomplete="off"

        enctype="multipart/form-data">

        <input

            type="hidden"

            id="id"

            name="id"

            value="1">

        <!-- ===================================================== -->
        <!-- IDENTITAS INSTITUSI -->
        <!-- ===================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-building text-primary"></i>

                    Identitas Institusi

                </h5>

            </div>

            <div class="card-body">

            <div class="row">

                    <div class="col-lg-9 mb-3">

                        <label class="form-label">
                            Badan Penyelenggara/Kementerian
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="foundation_name"
                            name="foundation_name"
                            placeholder="Contoh : Yayasan ... / Kementerian ...">

                    </div>

                    <div class="col-lg-3 mb-3">

                        <label class="form-label d-block">
                            Logo Institusi
                        </label>

                        <img
                            id="logoPreview"
                            src=""
                            alt="Logo"
                            style="height:48px; display:none; margin-bottom:8px;">

                        <input
                            type="file"
                            class="form-control"
                            id="logo"
                            name="logo"
                            accept=".png,.jpg,.jpeg">

                        <small class="text-muted">Kosongkan apabila tidak ingin mengganti logo.</small>

                    </div>

                </div>

                <div class="row">

                    <div class="col-lg-7 mb-3">

                        <label class="form-label">

                            Nama Institusi

                        </label>

                        <input

                            type="text"

                            class="form-control"

                            id="institution_name"

                            name="institution_name"

                            placeholder="Nama lengkap institusi">

                    </div>

                    <div class="col-lg-2 mb-3">

                        <label class="form-label">

                            Singkatan

                        </label>

                        <input

                            type="text"

                            class="form-control"

                            id="institution_short_name"

                            name="institution_short_name"

                            placeholder="Contoh : STIKES">

                    </div>

                    <div class="col-lg-3 mb-3">

                        <label class="form-label">

                            Jenis Institusi

                        </label>

                        <select

                            class="form-select"

                            id="institution_type"

                            name="institution_type">

                            <option value="Akademi">

                                Akademi

                            </option>

                            <option value="Sekolah Tinggi">

                                Sekolah Tinggi

                            </option>

                            <option value="Institut">

                                Institut

                            </option>

                            <option value="Universitas">

                                Universitas

                            </option>

                        </select>

                    </div>

                </div>

                <div class="row">

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Status Perguruan Tinggi

                        </label>

                        <select

                            class="form-select"

                            id="institution_status"

                            name="institution_status">

                            <option value="Swasta">

                                Swasta

                            </option>

                            <option value="Negeri">

                                Negeri

                            </option>

                        </select>

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Jabatan Pimpinan

                        </label>

                        <input

                            type="text"

                            class="form-control"

                            id="leader_title"

                            name="leader_title"

                            placeholder="Ketua / Rektor / Direktur">

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Nama Pimpinan

                        </label>

                        <input

                            type="text"

                            class="form-control"

                            id="leader_name"

                            name="leader_name"

                            placeholder="Nama pimpinan institusi">

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- END PART 1 -->
        <!-- ===================================================== -->

                <!-- ===================================================== -->
        <!-- AKREDITASI -->
        <!-- ===================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-award text-success"></i>

                    Akreditasi Institusi

                </h5>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-lg-3 mb-3">

                        <label class="form-label">

                            Status Akreditasi

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="accreditation_status"
                            name="accreditation_status"
                            placeholder="Unggul / Baik Sekali">

                    </div>

                    <div class="col-lg-5 mb-3">

                        <label class="form-label">

                            Nomor SK

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="accreditation_number"
                            name="accreditation_number">

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Lembaga Akreditasi

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="accreditation_agency"
                            name="accreditation_agency"
                            placeholder="LAM-PTKes / BAN-PT">

                    </div>

                </div>

                <div class="row">

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Berlaku Sampai

                        </label>

                        <input
                            type="date"
                            class="form-control"
                            id="accreditation_expired"
                            name="accreditation_expired">

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- KONTAK -->
        <!-- ===================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-geo-alt text-danger"></i>

                    Alamat & Kontak

                </h5>

            </div>

            <div class="card-body">

                <div class="mb-3">

                    <label class="form-label">

                        Alamat

                    </label>

                    <textarea

                        class="form-control"

                        rows="3"

                        id="address"

                        name="address"></textarea>

                </div>

                <div class="row">

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Kota

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="city"
                            name="city">

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Provinsi

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="province"
                            name="province">

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Kode Pos

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="postal_code"
                            name="postal_code">

                    </div>

                </div>

                <div class="row">

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Telepon

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="phone"
                            name="phone">

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Email

                        </label>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email">

                    </div>

                    <div class="col-lg-4 mb-3">

                        <label class="form-label">

                            Website

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="website"
                            name="website"
                            placeholder="https://">

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- PIMPINAN -->
        <!-- ===================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-people text-warning"></i>

                    Pimpinan & Penjaminan Mutu

                </h5>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-lg-6 mb-3">

                        <label class="form-label">

                            Nama Unit Penjaminan Mutu

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="quality_office_name"
                            name="quality_office_name">

                    </div>

                    <div class="col-lg-6 mb-3">

                        <label class="form-label">

                            Kepala Unit Penjaminan Mutu

                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="quality_office_head"
                            name="quality_office_head">

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- END PART 2 -->
        <!-- ===================================================== -->

                <!-- ===================================================== -->
        <!-- VISI -->
        <!-- ===================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-bullseye text-primary"></i>

                    Visi Institusi

                </h5>

            </div>

            <div class="card-body">

                <textarea

                    class="form-control"

                    id="vision"

                    name="vision"

                    rows="5"

                    placeholder="Masukkan visi institusi..."></textarea>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- MISI -->
        <!-- ===================================================== -->

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-white">

                <h5 class="mb-0">

                    <i class="bi bi-list-check text-success"></i>

                    Misi Institusi

                </h5>

            </div>

            <div class="card-body">

                <textarea

                    class="form-control"

                    id="mission"

                    name="mission"

                    rows="7"

                    placeholder="Masukkan misi institusi..."></textarea>

                <small class="text-muted">

                    Gunakan penomoran jika terdiri dari beberapa misi.

                </small>

            </div>

        </div>

        <!-- ===================================================== -->
        <!-- END PART 3 -->
        <!-- ===================================================== -->

    </form>

</div>

<?php

require_once __DIR__ . '/../../layouts/footer.php';

?>

<!-- ===================================================== -->
<!-- END PART 4 -->
<!-- ===================================================== -->

<?php
/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Institution Profile
|--------------------------------------------------------------------------
|
| Sprint M4.3
|
| Status :
|
| ✓ Repository
| ✓ Service
| ✓ API GET
| ✓ API UPDATE
| ✓ Layout
| ✓ UI
|
| Selanjutnya :
|
| assets/js/institution.js
|
|--------------------------------------------------------------------------
*/
?>