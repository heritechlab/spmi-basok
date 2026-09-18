<!-- ==========================================================
ASSIGNMENT TABLE
========================================================== -->

<div class="card border-0 shadow-sm">

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-primary">

                    <tr>
                        <th width="60" class="text-center">#</th>
                        <th width="180">Assignment</th>
                        <th width="150">Auditee</th>
                        <th width="220">Lead Auditor</th>
                        <th width="160">Periode</th>
                        <th width="120">Jadwal</th>
                        <th width="110">Status</th>
                        <th width="170">Progress</th>
                        <th width="110">Aksi</th>
                    </tr>

                </thead>

                <tbody>

                <?php

                if($assignments && $assignments->num_rows>0):

                $no=1;

                while($row=$assignments->fetch_assoc()):

                ?>

                <tr>

                    <td class="text-center fw-semibold">
                         <?= $no++ ?>
                    </td>

                    <td>

                        <strong>

                            <?= htmlspecialchars($row['assignment_number']) ?>

                        </strong>

                    </td>

                    <td>

                        <?= htmlspecialchars($row['auditee_name']) ?>

                    </td>

                    <td>

                        <?= htmlspecialchars($row['lead_auditor_name']) ?>

                    </td>

                    <td>

                        <?= htmlspecialchars($row['period_name']) ?>

                    </td>

                    <td>

                        <?= formatDate($row['audit_date']) ?>

                    </td>

                    <td>

                        <?= badgeStatus($row['status']) ?>

                    </td>
       <td>

<?php $progress = (int)($row['progress_percent'] ?? 0); ?>

<div class="progress"
     style="height:14px;
            border-radius:30px;
            background:#edf2f7;">

    <div class="progress-bar bg-success"
         role="progressbar"
         style="width:<?= $progress ?>%;
                border-radius:30px;">
    </div>

</div>

</td>

                    <td class="text-center">

                        <div class="btn-group">

                            <a
                                href="detail.php?id=<?= $row['id'] ?>"
                                class="btn btn-sm btn-outline-primary">

                                <i class="bi bi-eye"></i>

                            </a>

                            <a
                                href="edit.php?id=<?= $row['id'] ?>"
                                class="btn btn-sm btn-outline-warning">

                                <i class="bi bi-pencil"></i>

                            </a>

                            <a
                                href="delete.php?id=<?= $row['id'] ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Hapus penugasan audit ini?')">

                                <i class="bi bi-trash"></i>

                            </a>

                        </div>

                    </td>

                </tr>

                <?php

                endwhile;

                else:

                ?>

                <tr>

                    <td colspan="9" class="text-center py-5">

                        <i class="bi bi-inbox display-5 text-secondary"></i>

                        <br><br>

                        Belum ada data Penugasan Audit.

                    </td>

                </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>