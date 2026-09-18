<div class="card border-0 shadow-sm h-100">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">
            <i class="bi bi-people me-2 text-primary"></i>
            Tim Auditor
        </h5>

    </div>

    <div class="card-body">

        <table class="table table-borderless table-sm">

            <tr>
                <th width="40%">Lead Auditor</th>
                <td><?= htmlspecialchars($assignment['lead_auditor_name']) ?></td>
            </tr>

            <tr>
                <th>Koordinator</th>
                <td><?= htmlspecialchars($assignment['coordinator_name']) ?></td>
            </tr>

        </table>

        <hr>

        <h6 class="fw-bold mb-3">
            Anggota Tim
        </h6>

        <?php if($teamMembers && $teamMembers->num_rows>0): ?>

            <div class="list-group list-group-flush">

                <?php while($member = $teamMembers->fetch_assoc()): ?>

                    <div class="list-group-item px-0">

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <strong>
                                    <?= htmlspecialchars($member['full_name']) ?>
                                </strong>

                                <br>

                                <small class="text-muted">
                                    <?= htmlspecialchars($member['role_in_team']) ?>
                                </small>

                            </div>

                            <span class="badge bg-success">
                                <?= htmlspecialchars($member['status']) ?>
                            </span>

                        </div>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <div class="text-center text-muted py-3">

                Belum ada anggota tim auditor.

            </div>

        <?php endif; ?>

    </div>

</div>