<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../core/Auth.php';

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$config = [

    'title'      => 'Master Unit Kerja',

    'subtitle'   => 'Pengelolaan struktur organisasi dan unit kerja SIQUA',

    'breadcrumb' => 'Master / Unit Kerja'

];

$pageCss    = 'assets/css/unit.css';
$pageScript = 'assets/js/unit.js';

require_once __DIR__ . '/../../layouts/app.php';

$repository = new UnitRepository($conn);
$service    = new UnitService($repository);

?>

<div class="container-fluid">

    <!-- ========================================================= -->
    <!-- PAGE HEADER -->
    <!-- ========================================================= -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                <i class="bi bi-diagram-3-fill text-primary"></i>

                <?= $config['title']; ?>

            </h3>

            <div class="text-muted">

                <?= $config['breadcrumb']; ?>

            </div>

        </div>

        <div>

            <button

                type="button"

                class="btn btn-success"

                id="btnRefresh">

                <i class="bi bi-arrow-repeat"></i>

                Refresh

            </button>

            <button class="btn btn-primary <?= Auth::canManage() ? '' : 'btn-locked' ?>" id="btnAddUnit"
                <?= Auth::canManage() ? '' : 'title="Anda tidak memiliki akses untuk menambah data"' ?>>
                <i class="bi bi-plus-circle"></i>
                Tambah Unit
            </button>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- INFORMATION -->
    <!-- ========================================================= -->

    <div class="alert alert-primary border-0 shadow-sm mb-4">

        <div class="row align-items-center">

            <div class="col-lg-1 text-center">

                <i class="bi bi-building fs-1"></i>

            </div>

            <div class="col-lg-11">

                <h6 class="mb-1">

                    Struktur Organisasi Institusi

                </h6>

                <small>

                    Master Unit Kerja merupakan referensi utama seluruh
                    struktur organisasi pada SIQUA. Data Unit Kerja
                    digunakan oleh Modul Audit Mutu Internal, Penugasan
                    Auditor, Dashboard, Instrumen Audit, Laporan Audit,
                    serta berbagai proses akademik lainnya.

                </small>

            </div>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- FILTER CARD -->
    <!-- ========================================================= -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-white">

            <strong>

                <i class="bi bi-funnel"></i>

                Filter Data

            </strong>

        </div>

        <div class="card-body">

            <div class="row">

                <!-- SEARCH -->

                <div class="col-lg-4 mb-3">

                    <label class="form-label">

                        Pencarian

                    </label>

                    <input

                        type="text"

                        class="form-control"

                        id="searchUnit"

                        placeholder="Cari kode atau nama unit...">

                </div>

                <!-- TYPE -->

                <div class="col-lg-3 mb-3">

                    <label class="form-label">

                        Jenis Unit

                    </label>

                    <select

                        class="form-select"

                        id="filterType">

                        <option value="">

                            Semua Jenis

                        </option>

                        <option value="Fakultas">

                            Fakultas

                        </option>

                        <option value="Program Studi">

                            Program Studi

                        </option>

                        <option value="Lembaga">

                            Lembaga

                        </option>

                        <option value="UPT">

                            UPT

                        </option>

                        <option value="Pusat">

                            Pusat

                        </option>

                        <option value="Biro">

                            Biro

                        </option>

                        <option value="Bagian">

                            Bagian

                        </option>

                        <option value="Direktorat">

                            Direktorat

                        </option>

                        <option value="Unit Pendukung">

                            Unit Pendukung

                        </option>

                    </select>

                </div>

                <!-- STATUS -->

                <div class="col-lg-2 mb-3">

                    <label class="form-label">

                        Status

                    </label>

                    <select

                        class="form-select"

                        id="filterStatus">

                        <option value="">

                            Semua

                        </option>

                        <option value="1">

                            Aktif

                        </option>

                        <option value="0">

                            Nonaktif

                        </option>

                    </select>

                </div>

                <!-- ACTION -->

                <div class="col-lg-3 mb-3 d-flex align-items-end">

                    <button

                        type="button"

                        class="btn btn-outline-secondary"

                        id="btnReset">

                        <i class="bi bi-arrow-clockwise"></i>

                        Reset Filter

                    </button>

                 </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ========================================================= -->
    <!-- DATA TABLE -->
    <!-- ========================================================= -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="mb-0">

                        <i class="bi bi-table text-primary"></i>

                        Daftar Unit Kerja

                    </h5>

                    <small class="text-muted">

                        Data Unit Kerja yang terdaftar pada SIQUA

                    </small>

                </div>

                <div>

                    <span

                        class="badge bg-primary fs-6"

                        id="totalUnitBadge">

                        0 Unit

                    </span>

                </div>

            </div>

        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table

                    class="table table-hover table-bordered align-middle w-100"

                    id="unitTable">

                    <thead class="table-light">

                        <tr>

                            <th width="90">Kode</th>

                            <th>Nama Unit Kerja</th>

                            <th width="140">Jenis</th>

                            <th>Nama Penanggung Jawab</th>

                            <th width="90" class="text-center">
                                Audit
                            </th>

                            <th width="120" class="text-center">
                                Aksi
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td

                                colspan="8"

                                class="text-center py-5 text-muted">

                                <div class="spinner-border spinner-border-sm text-primary mb-2"></div>

                                <div>

                                    Memuat data Unit Kerja...

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>
        <!-- ========================================================= -->
    <!-- MODAL CRUD UNIT -->
    <!-- ========================================================= -->

    <div
        class="modal fade"
        id="unitModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false">

        <div class="modal-dialog modal-xl">

            <div class="modal-content border-0 shadow">

                <!-- ========================================== -->
                <!-- HEADER -->
                <!-- ========================================== -->

                <div class="modal-header bg-primary text-white">

                    <h5
                        class="modal-title"
                        id="unitModalTitle">

                        Tambah Unit Kerja

                    </h5>

                    <button

                        type="button"

                        class="btn-close btn-close-white"

                        data-bs-dismiss="modal">

                    </button>

                </div>

                <!-- ========================================== -->
                <!-- FORM -->
                <!-- ========================================== -->

                <form id="unitForm">

                    <input

                        type="hidden"

                        id="id"

                        name="id"

                        value="0">

                    <div class="modal-body">

                        <!-- =============================== -->
                        <!-- BARIS 1 -->
                        <!-- =============================== -->

                        <div class="row">

                            <div class="col-lg-2 mb-3">

                                <label class="form-label">

                                    Kode Unit

                                </label>

                                <input

                                    type="text"

                                    class="form-control"

                                    id="code"

                                    name="code"

                                    maxlength="20"

                                    required>

                            </div>

                            <div class="col-lg-3 mb-3">

                                <label class="form-label">

                                    Singkatan

                                </label>

                                <input

                                    type="text"

                                    class="form-control"

                                    id="short_name"

                                    name="short_name">

                            </div>

                            <div class="col-lg-3 mb-3">

                                <label class="form-label">

                                    Jenis Unit

                                </label>

                                <select

                                    class="form-select"

                                    id="type"

                                    name="type"

                                    required>

                                    <option value="">-- Pilih --</option>

                                    <option>Fakultas</option>

                                    <option>Program Studi</option>

                                    <option>Lembaga</option>

                                    <option>UPT</option>

                                    <option>Pusat</option>

                                    <option>Biro</option>

                                    <option>Bagian</option>

                                    <option>Direktorat</option>

                                    <option>Unit Pendukung</option>

                                </select>

                            </div>

                            <div class="col-lg-4 mb-3">

                                <label class="form-label">

                                    Parent Unit

                                </label>

                                <select

                                    class="form-select"

                                    id="parent_id"

                                    name="parent_id">

                                    <option value="">

                                        Tidak Ada

                                    </option>

                                </select>

                            </div>

                        </div>

                        <!-- =============================== -->
                        <!-- BARIS 2 -->
                        <!-- =============================== -->

                        <div class="row">

                            <div class="col-lg-8 mb-3">

                                <label class="form-label">

                                    Nama Unit Kerja

                                </label>

                                <input

                                    type="text"

                                    class="form-control"

                                    id="name"

                                    name="name"

                                    required>

                            </div>

                            <div class="col-lg-4 mb-3">

                                <label class="form-label">

                                    Kepala Unit

                                </label>

                                <input

                                    type="text"

                                    class="form-control"

                                    id="head_name"

                                    name="head_name">

                            </div>

                        </div>

                        <!-- =============================== -->
                        <!-- BARIS 3 -->
                        <!-- =============================== -->

                        <div class="row">

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

                                    Nomor Telepon

                                </label>

                                <input

                                    type="text"

                                    class="form-control"

                                    id="phone"

                                    name="phone">

                            </div>

                            <div class="col-lg-2 mb-3">

                                <label class="form-label">

                                    Auditable

                                </label>

                                <select

                                    class="form-select"

                                    id="is_auditable"

                                    name="is_auditable">

                                    <option value="1">

                                        Ya

                                    </option>

                                    <option value="0">

                                        Tidak

                                    </option>

                                </select>

                            </div>

                            <div class="col-lg-2 mb-3">

                                <label class="form-label">

                                    Status

                                </label>

                                <select

                                    class="form-select"

                                    id="status"

                                    name="status">

                                    <option value="1">

                                        Aktif

                                    </option>

                                    <option value="0">

                                        Nonaktif

                                    </option>

                                </select>

                            </div>

                        </div>

                        <!-- =============================== -->
                        <!-- BARIS 4 -->
                        <!-- =============================== -->

                        <div class="row">

                            <div class="col-lg-2 mb-3">

                                <label class="form-label">

                                    Urutan

                                </label>

                                <input

                                    type="number"

                                    class="form-control"

                                    id="sort_order"

                                    name="sort_order"

                                    value="1">

                            </div>

                            <div class="col-lg-10 mb-3">

                                <label class="form-label">

                                    Deskripsi

                                </label>

                                <textarea

                                    class="form-control"

                                    id="description"

                                    name="description"

                                    rows="4"></textarea>

                            </div>

                        </div>

                    </div>

                    <!-- ========================================== -->
                    <!-- FOOTER -->
                    <!-- ========================================== -->

                    <div class="modal-footer">

                        <button

                            type="button"

                            class="btn btn-light"

                            data-bs-dismiss="modal">

                            <i class="bi bi-x-circle"></i>

                            Batal

                        </button>

                        <button
                            type="submit"
                            id="btnSaveUnit"
                            class="btn btn-primary">

                            Simpan Data

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

        <!-- ========================================================= -->
    <!-- LOADING MODAL -->
    <!-- ========================================================= -->

    <div
        class="modal fade"
        id="loadingModal"
        tabindex="-1"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content border-0 shadow">

                <div class="modal-body text-center py-5">

                    <div
                        class="spinner-border text-primary"
                        style="width:3rem;height:3rem;"
                        role="status">

                    </div>

                    <h5 class="mt-4 mb-2">

                        Memproses Data...

                    </h5>

                    <div class="text-muted">

                        Mohon tunggu sebentar.

                    </div>

                </div>

            </div>

        </div>

    </div>

<!-- ========================================================= -->
    <!-- JAVASCRIPT CONFIGURATION -->
    <!-- ========================================================= -->

    <script>

        /*
        |--------------------------------------------------------------------------
        | Master Unit Configuration
        |--------------------------------------------------------------------------
        */

        window.SIQUA_UNIT = {

            table : "#unitTable",

            modal : "#unitModal",

            form : "#unitForm",

            loading : "#loadingModal",

            search : "#searchUnit",

            filterType : "#filterType",

            filterStatus : "#filterStatus",

            btnAdd : "#btnAddUnit",

            btnSave : "#btnSaveUnit",

            btnRefresh : "#btnRefresh",

            btnReset : "#btnReset",

            totalBadge : "#totalUnitBadge"

        };

        /*
        |--------------------------------------------------------------------------
        | Default Object
        |--------------------------------------------------------------------------
        */

        window.UnitDefault = {

            id : 0,

            code : "",

            short_name : "",

            name : "",

            type : "",

            parent_id : "",

            head_name : "",

            email : "",

            phone : "",

            description : "",

            is_auditable : 1,

            status : 1,

            sort_order : 1

        };

window.SIQUA_CAN_MANAGE = <?= Auth::canManage() ? 'true' : 'false' ?>;

    </script>

<?php
require_once __DIR__ . '/../../layouts/footer.php';
?>