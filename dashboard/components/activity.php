<?php
// Ambil 8 aktivitas terbaru
$qActivity = mysqli_query($conn, "
    SELECT
        activity_logs.*,
        users.full_name
    FROM activity_logs
    LEFT JOIN users
        ON users.id = activity_logs.user_id
    ORDER BY activity_logs.created_at DESC
    LIMIT 8
");
?>
<div class="col-lg-7 mb-4">

        <div class="widget h-100">

            <div class="widget-header mb-4">

                <h4>
                    <i class="bi bi-clock-history text-primary"></i>
                    Aktivitas Terbaru
                </h4>

                <small>
                    Semua aktivitas terbaru SIQUA
                </small>

            </div>

            <div class="activity-list">

                <?php if(mysqli_num_rows($qActivity)>0): ?>

                    <?php while($row=mysqli_fetch_assoc($qActivity)): ?>

                        <div class="activity-item">

                            <div class="activity-icon">

                                <i class="bi bi-check-circle-fill"></i>

                            </div>

                            <div class="activity-content">

                                <h6>

                                    <?= htmlspecialchars($row['activity']) ?>

                                </h6>

                                <small>

                                    <?= htmlspecialchars($row['full_name'] ?? 'Administrator') ?>

                                    •

                                    <?= date('d M Y H:i', strtotime($row['created_at'])) ?>

                                </small>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="text-center py-5">

                        <i class="bi bi-clock-history display-5 text-secondary"></i>

                        <p class="mt-3 mb-0">

                            Belum ada aktivitas tercatat.

                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>