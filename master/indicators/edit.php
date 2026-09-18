<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';

$config = [
    'title'    => 'Edit Master Indikator',
    'subtitle' => 'Mengubah data indikator audit mutu internal'
];

require_once __DIR__ . '/../unit/repository.php';

$repository = new IndicatorRepository($conn);
$service    = new IndicatorService($repository);

$id = (int)($_GET['id'] ?? 0);

if($id<=0){

    die("ID tidak valid.");

}

$result = $service->getById($id);

if (!$result['success']) {
    die('Data indikator tidak ditemukan.');
}

$indicator = $result['data'];

if(!$indicator){

    die("Data tidak ditemukan.");

}

$unitRepo = new UnitRepository($conn);
$units = $unitRepo->getAll();
$selectedUnitIds = $repository->getIndicatorUnitIds((int) $indicator['id']);

$standards = $repository->getStandards();

$standardExists = false;

foreach ($standards as $s) {
    if ((int) $s['id'] === (int) $indicator['standard_id']) {
        $standardExists = true;
        break;
    }
}

if (!$standardExists && !empty($indicator['standard_id'])) {
    $currentStandard = $conn->query(
        "SELECT id, code, name FROM standards WHERE id = " . (int) $indicator['standard_id']
    )->fetch_assoc();

    if ($currentStandard) {
        $standards[] = $currentStandard;
    }
}

$pageScript = 'assets/js/indicator-edit.js';

require_once __DIR__ . '/../../layouts/app.php';

?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3><?= $config['title']; ?></h3>

            <p class="text-muted">

                <?= $config['subtitle']; ?>

            </p>

        </div>

        <a
            href="<?= BASE_URL ?>master/indicators/"
            class="btn btn-secondary">

            <i class="bi bi-arrow-left"></i>

            Kembali

        </a>

    </div>
    <div class="card shadow-sm">

    <div class="card-header">

        <strong>Form Edit Indikator</strong>

    </div>

    <div class="card-body">

        <form id="indicatorForm">

            <input
                type="hidden"
                name="id"
                value="<?= $indicator['id']; ?>">

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Kode Indikator

                        </label>

                        <input
                            type="text"
                            name="item_code"
                            class="form-control"
                            value="<?= htmlspecialchars($indicator['item_code']); ?>">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Standar

                        </label>

                        <select
                            name="standard_id"
                            class="form-select"
                            data-statement-id="<?= (int) ($indicator['statement_id'] ?? 0) ?>">

                            <option value="">
                                -- Pilih Standar --
                            </option>

                            <?php foreach ($standards as $row): ?>

                                <option
                                    value="<?= $row['id']; ?>"
                                    <?= $row['id'] == $indicator['standard_id'] ? 'selected' : ''; ?>>

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

                                <option
                                    value="IKU Wajib"
                                    <?= $indicator['indicator_type']=='IKU Wajib' ? 'selected':'';?>>

                                    IKU Wajib

                                </option>

                                <option
                                    value="IKU Pilihan"
                                    <?= $indicator['indicator_type']=='IKU Pilihan' ? 'selected':'';?>>

                                    IKU Pilihan

                                </option>

                                <option
                                    value="IKU PT"
                                    <?= $indicator['indicator_type']=='IKU PT' ? 'selected':'';?>>

                                    IKU PT

                                </option>

                                <option
                                    value="IKT"
                                    <?= $indicator['indicator_type']=='IKT' ? 'selected':'';?>>

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
                        Daftar Pernyataan diambil dari Master Standar.
                    </small>

                </div>

<div class="mb-3">

                    <label class="form-label">

                        Indikator

                    </label>

                    <textarea
                        name="indicator"
                        class="form-control"
                        rows="3"><?= htmlspecialchars($indicator['indicator']); ?>
                    </textarea>

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Strategi Pencapaian Standar

                    </label>

                    <textarea
                        name="strategi"
                        class="form-control"
                        rows="3"><?= htmlspecialchars($indicator['strategi'] ?? ''); ?>
                    </textarea>

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
                            value="<?= htmlspecialchars($indicator['target']); ?>">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Metode Verifikasi

                        </label>

                        <select
                            name="verification_method"
                            class="form-select">

                            <option
                                value="Dokumen"
                                <?= $indicator['verification_method']=='Dokumen' ? 'selected' : ''; ?>>
                                Dokumen
                            </option>

                            <option
                                value="Observasi"
                                <?= $indicator['verification_method']=='Observasi' ? 'selected' : ''; ?>>
                                Observasi
                            </option>

                            <option
                                value="Wawancara"
                                <?= $indicator['verification_method']=='Wawancara' ? 'selected' : ''; ?>>
                                Wawancara
                            </option>

                            <option
                                value="Kombinasi"
                                <?= $indicator['verification_method']=='Kombinasi' ? 'selected' : ''; ?>>
                                Kombinasi
                            </option>

                        </select>

                    </div>

                    <div class="col-md-3 mb-3">

                        <label class="form-label">

                            Unit Kerja

                        </label>

                        <div class="border rounded p-2" style="max-height:150px; overflow-y:auto;">

                            <?php foreach ($units as $u): ?>

                                <?php $checked = in_array($u['id'], $selectedUnitIds) ? 'checked' : ''; ?>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="unit_ids[]" value="<?= $u['id'] ?>" id="unit_<?= $u['id'] ?>" <?= $checked ?>>
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
                            value="<?= htmlspecialchars((string)$indicator['weight']); ?>"
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
                            name="status"
                            value="1"
                            <?= $indicator['status']==1 ? 'checked' : ''; ?>>

                        <label class="form-check-label">

                            Aktif

                        </label>

                    </div>

                    <div class="form-check form-check-inline">

                        <input
                            class="form-check-input"
                            type="radio"
                            name="status"
                            value="0"
                            <?= $indicator['status']==0 ? 'checked' : ''; ?>>

                        <label class="form-check-label">

                            Nonaktif

                        </label>

                    </div>

                </div>

                <hr>

                <button
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

include __DIR__ . '/../../layouts/footer.php';

?>