<div class="card border-0 shadow-sm h-100">

    <div class="card-header bg-white">

        <h5 class="mb-0 fw-bold">
            <i class="bi bi-calendar-event me-2 text-primary"></i>
            Jadwal Audit
        </h5>

    </div>

    <div class="card-body">

        <table class="table table-borderless table-sm mb-0">

            <tr>
                <th width="42%">Tanggal Penugasan</th>
                <td>
                    <?= !empty($assignment['assignment_date'])
                        ? formatDate($assignment['assignment_date'])
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Tanggal Audit</th>
                <td>
                    <?= !empty($assignment['audit_date'])
                        ? formatDate($assignment['audit_date'])
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Mulai Audit</th>
                <td>
                    <?= !empty($assignment['audit_start'])
                        ? date('d M Y H:i', strtotime($assignment['audit_start']))
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Selesai Audit</th>
                <td>
                    <?= !empty($assignment['audit_finish'])
                        ? date('d M Y H:i', strtotime($assignment['audit_finish']))
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Opening Meeting</th>
                <td>
                    <?= !empty($assignment['opening_meeting'])
                        ? date('d M Y H:i', strtotime($assignment['opening_meeting']))
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Closing Meeting</th>
                <td>
                    <?= !empty($assignment['closing_meeting'])
                        ? date('d M Y H:i', strtotime($assignment['closing_meeting']))
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Reminder</th>
                <td>
                    <?= !empty($assignment['reminder_date'])
                        ? formatDate($assignment['reminder_date'])
                        : '-' ?>
                </td>
            </tr>

            <tr>
                <th>Tanggal Selesai</th>
                <td>
                    <?= !empty($assignment['completion_date'])
                        ? formatDate($assignment['completion_date'])
                        : '-' ?>
                </td>
            </tr>

        </table>

    </div>

</div>