<div class="card shadow-sm border-0">

    <div class="card-body">

        <table
            id="standardTable"
            class="table table-hover table-striped align-middle w-100">

            <thead>

                <tr>

                    <th width="60">No</th>

                    <th>Kode</th>

                    <th>Kategori</th>

                    <th>Nama Standar</th>

                    <th>Tanggal Terbit</th>

                    <th>Revisi</th>

                    <th>Status</th>

                    <th>Dokumen</th>

                    <th width="140">Aksi</th>

                </tr>

            </thead>

            <tbody>

<?php if (empty($standards)) : ?>

<tr>

    <td colspan="9" class="text-center py-5">

        <i class="bi bi-inbox fs-1 text-secondary d-block mb-2"></i>

        Belum ada data standar.

    </td>

</tr>

<?php else : ?>

<?php foreach ($standards as $i => $row) : ?>

<tr>

    <!-- Nomor -->
    <td class="text-center">

        <?= $i + 1 ?>

    </td>

    <!-- Kode -->
    <td>

        <strong>

            <?= htmlspecialchars($row['code']) ?>

        </strong>

    </td>

    <!-- Kategori -->
    <td>

        <?= htmlspecialchars($row['category_name'] ?? '-') ?>

    </td>

    <!-- Nama Standar -->
    <td>

        <?= htmlspecialchars($row['name']) ?>

    </td>

    <!-- Tanggal Terbit -->
    <td class="text-center">

        <?= !empty($row['publish_date'])

            ? date('d-m-Y', strtotime($row['publish_date']))

            : '-'

        ?>

    </td>

    <!-- Revisi -->
    <td class="text-center">

        <?= htmlspecialchars($row['revision'] ?? '0') ?>

    </td>

    <!-- Status -->
    <td class="text-center">

        <?php

        $badge = ($row['status'] == 1)

            ? 'success'

            : 'secondary';

        ?>

        <span class="badge bg-<?= $badge ?>">

            <?= htmlspecialchars($row['status_name']) ?>

        </span>

    </td>

    <!-- Dokumen -->
    <td class="text-center">

        <?php if (!empty($row['document_file'])) : ?>

            <button

                type="button"

                class="btn btn-sm btn-outline-danger btnPreview"

                data-file="<?= htmlspecialchars($row['document_file']) ?>"

                title="Preview PDF">

                <i class="bi bi-file-earmark-pdf-fill"></i>

            </button>

        <?php else : ?>

            <span class="text-muted">

                -

            </span>

        <?php endif; ?>

    </td>

    <!-- Action -->
    <td class="text-center">

        <div class="btn-group btn-group-sm">

            <button

                type="button"

                class="btn btn-outline-primary btnEdit"

                data-id="<?= $row['id'] ?>"

                title="Edit">

                <i class="bi bi-pencil"></i>

            </button>

            <button

                type="button"

                class="btn btn-outline-danger btnDelete"

                data-id="<?= $row['id'] ?>"

                title="Hapus">

                <i class="bi bi-trash"></i>

            </button>

        </div>

    </td>

</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

        </table>

    </div>

</div>