<!-- ==========================================================
EXECUTIVE KPI
========================================================== -->

<div class="row g-3 mb-4">

    <?php

    $cards = [

        [
            'title' => 'Total',
            'value' => $statistics['total'] ?? 0,
            'icon'  => 'bi bi-folder2-open',
            'color' => 'primary'
        ],

        [
            'title' => 'Draft',
            'value' => $statistics['draft'] ?? 0,
            'icon'  => 'bi bi-pencil-square',
            'color' => 'secondary'
        ],

        [
            'title' => 'Dijadwalkan',
            'value' => $statistics['scheduled'] ?? 0,
            'icon'  => 'bi bi-calendar-check',
            'color' => 'info'
        ],

        [
            'title' => 'Berlangsung',
            'value' => $statistics['running'] ?? 0,
            'icon'  => 'bi bi-play-circle',
            'color' => 'warning'
        ],

        [
            'title' => 'Selesai',
            'value' => $statistics['finished'] ?? 0,
            'icon'  => 'bi bi-check-circle',
            'color' => 'success'
        ],

        [
            'title' => 'Batal',
            'value' => $statistics['cancelled'] ?? 0,
            'icon'  => 'bi bi-x-circle',
            'color' => 'danger'
        ]

    ];

    foreach($cards as $card):
    ?>

    <div class="col-xl-2 col-lg-4 col-md-6">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <div>

                        <small class="text-muted">

                            <?= $card['title'] ?>

                        </small>

                        <h3 class="fw-bold mt-2 mb-0">

                            <?= $card['value'] ?>

                        </h3>

                    </div>

                    <div class="fs-2 text-<?= $card['color'] ?>">

                        <i class="<?= $card['icon'] ?>"></i>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php endforeach; ?>

</div>