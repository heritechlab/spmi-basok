<div class="card border-0 shadow-sm h-100">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">
            <i class="bi bi-info-circle me-2 text-primary"></i>
            Informasi Audit
        </h5>

    </div>

    <div class="card-body">

        <table class="table table-borderless table-sm mb-0">

            <tr>
                <th width="40%">Nomor Assignment</th>
                <td><?= htmlspecialchars($assignment['assignment_number']) ?></td>
            </tr>

            <tr>
                <th>Jenis Audit</th>
                <td><?= htmlspecialchars($assignment['audit_type']) ?></td>
            </tr>

            <tr>
                <th>Periode Audit</th>
                <td><?= htmlspecialchars($assignment['period_name']) ?></td>
            </tr>

            <tr>
                <th>Auditee</th>
                <td><?= htmlspecialchars($assignment['auditee_name']) ?></td>
            </tr>

            <tr>
                <th>Lead Auditor</th>
                <td><?= htmlspecialchars($assignment['lead_auditor_name']) ?></td>
            </tr>

            <tr>
                <th>Koordinator</th>
                <td><?= htmlspecialchars($assignment['coordinator_name']) ?></td>
            </tr>

            <tr>
                <th>Metode Audit</th>
                <td><?= htmlspecialchars($assignment['audit_method']) ?></td>
            </tr>

            <tr>
                <th>Lokasi Audit</th>
                <td><?= htmlspecialchars($assignment['audit_location']) ?></td>
            </tr>

            <tr>
                <th>Risk Level</th>
                <td><?= htmlspecialchars($assignment['risk_level']) ?></td>
            </tr>

            <tr>
                <th>Status</th>
                <td><?= badgeStatus($assignment['status']) ?></td>
            </tr>

        </table>

    </div>

</div>