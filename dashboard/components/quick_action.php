<div class="row mt-3 mb-2 g-3">

    <div class="col-6 col-lg-3">
        <a href="<?= BASE_URL ?>master/standards/" class="text-decoration-none">
            <div class="card shadow-sm h-100 quick-color-card" style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="quick-icon-circle">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right" style="color:rgba(255,255,255,0.6); font-size:16px;"></i>
                    </div>
                    <div class="quick-card-number"><?= $totalStandar ?></div>
                    <div class="quick-card-label">Standar Mutu</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <a href="<?= BASE_URL ?>master/indicators/" class="text-decoration-none">
            <div class="card shadow-sm h-100 quick-color-card" style="background:linear-gradient(135deg, #fbbf24, #d97706);">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="quick-icon-circle">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right" style="color:rgba(255,255,255,0.6); font-size:16px;"></i>
                    </div>
                    <div class="quick-card-number"><?= $totalIndikator ?></div>
                    <div class="quick-card-label">Indikator Mutu</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <a href="<?= BASE_URL ?>master/periods/" class="text-decoration-none">
            <div class="card shadow-sm h-100 quick-color-card" style="background:linear-gradient(135deg, #4ade80, #16a34a);">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="quick-icon-circle">
                            <i class="bi bi-calendar-plus-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right" style="color:rgba(255,255,255,0.6); font-size:16px;"></i>
                    </div>
                    <div class="quick-card-number"><?= $totalAudit ?></div>
                    <div class="quick-card-label">Periode Audit</div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-6 col-lg-3">
        <a href="<?= BASE_URL ?>audit/assignments/" class="text-decoration-none">
            <div class="card shadow-sm h-100 quick-color-card" style="background:linear-gradient(135deg, #f87171, #dc2626);">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="quick-icon-circle">
                            <i class="bi bi-clipboard-plus-fill"></i>
                        </div>
                        <i class="bi bi-arrow-up-right" style="color:rgba(255,255,255,0.6); font-size:16px;"></i>
                    </div>
                    <div class="quick-card-number"><?= $totalPenugasan ?></div>
                    <div class="quick-card-label">Penugasan Audit</div>
                </div>
            </div>
        </a>
    </div>

</div>