<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$config = [
    'title'    => 'Tambah Master Indikator',
    'subtitle' => 'Menambahkan indikator audit mutu internal'
];

require_once __DIR__ . '/../unit/repository.php';

$repository = new IndicatorRepository($conn);
$service    = new IndicatorService($repository);

/*
|--------------------------------------------------------------------------
| Ambil daftar standar & unit kerja
|--------------------------------------------------------------------------
*/

$standards = $repository->getStandards();

$unitRepo = new UnitRepository($conn);
$units = $unitRepo->getAll();

$pageScript = '';

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="mb-1">
                <?= $config['title']; ?>
            </h3>

            <p class="text-muted mb-0">
                <?= $config['subtitle']; ?>
            </p>

        </div>

        <a href="<?= BASE_URL ?>master/indicators/"
           class="btn btn-secondary">

            <i class="bi bi-arrow-left"></i>

            Kembali

        </a>

    </div>

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>Form Master Indikator</strong>

        </div>

        <div class="card-body">

            <form id="indicatorForm">

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Kode Indikator

                        </label>

                        <input
                            type="text"
                            name="item_code"
                            class="form-control"
                            placeholder="Contoh : 1.1">

                    </div>

                    <div class="col-md-8 mb-3">

                        <label class="form-label">

                            Standar

                        </label>

                        <select
                            name="standard_id"
                            class="form-select">

                            <option value="">
                                -- Pilih Standar --
                            </option>

                            <?php foreach($standards as $row): ?>

                                <option value="<?= $row['id']; ?>">

                                    <?= htmlspecialchars($row['code']); ?>

                                    -

                                    <?= htmlspecialchars($row['name']); ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Jenis Indikator

                        </label>

                        <select
                            name="indicator_type"
                            class="form-select">

                            <option value="IKU Wajib">

                                IKU Wajib

                            </option>

                            <option value="IKU Pilihan">

                                IKU Pilihan

                            </option>

                            <option value="IKU PT">

                                IKU PT

                            </option>

                            <option value="IKT">

                                IKT

                            </option>

                        </select>

                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Pernyataan Standar

                    </label>

                    <select
                        name="statement_id"
                        id="statement_id"
                        class="form-select">

                        <option value="">
                            -- Pilih Standar terlebih dahulu --
                        </option>

                    </select>

                    <small class="text-muted">
                        Daftar Pernyataan diambil dari Master Standar. Belum ada pilihan? Tambahkan dulu di menu Master Standar.
                    </small>

                </div>

<div class="mb-3">

                    <label class="form-label">

                        Indikator

                    </label>

                    <textarea
                        name="indicator"
                        class="form-control"
                        rows="3"></textarea>

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Strategi Pencapaian Standar

                    </label>

                    <textarea
                        name="strategi"
                        class="form-control"
                        rows="3"
                        placeholder="Strategi/upaya yang dilakukan untuk mencapai standar ini"></textarea>

                </div>

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Target

                        </label>

                        <input
                            type="text"
                            name="target"
                            class="form-control"
                            placeholder="100%">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Metode Verifikasi

                        </label>

                        <select
                            name="verification_method"
                            class="form-select">

                            <option>Dokumen</option>

                            <option>Observasi</option>

                            <option>Wawancara</option>

                            <option>Kombinasi</option>

                        </select>

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Unit Kerja

                        </label>

                        <div class="border rounded p-2" style="max-height:150px; overflow-y:auto;">

                            <?php foreach ($units as $u): ?>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="unit_ids[]" value="<?= $u['id'] ?>" id="unit_<?= $u['id'] ?>">
                                    <label class="form-check-label small" for="unit_<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></label>
                                </div>

                            <?php endforeach; ?>

                        </div>

                        <small class="text-muted">Boleh dicentang lebih dari satu Unit.</small>

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Bobot

                        </label>

                        <input
                            type="number"
                            step="0.01"
                            value="1"
                            name="weight"
                            class="form-control">

                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label d-block">

                        Status

                    </label>

                    <div class="form-check form-check-inline">

                        <input
                            class="form-check-input"
                            type="radio"
                            checked
                            name="status"
                            value="1">

                        <label class="form-check-label">

                            Aktif

                        </label>

                    </div>

                    <div class="form-check form-check-inline">

                        <input
                            class="form-check-input"
                            type="radio"
                            name="status"
                            value="0">

                        <label class="form-check-label">

                            Nonaktif

                        </label>

                    </div>

                </div>

                <hr>

                <button
    id="btnSaveIndicator"
    type="submit"
    class="btn btn-primary">

    <i class="bi bi-save"></i>

    Simpan

</button>


                <a
                    href="<?= BASE_URL ?>master/indicators/"
                    class="btn btn-light">

                    Batal

                </a>

            </form>

        </div>

    </div>

</div>

<?php
$pageScript = 'assets/js/indicator-create.js';

include __DIR__.'/../../layouts/footer.php';

?>