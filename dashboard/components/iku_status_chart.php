<div class="col-lg-6 mb-4">

    <div class="widget h-100">

        <div class="widget-header mb-3">
            <h4>
                <i class="bi bi-bar-chart-steps"></i>
                Status Capaian per Jenis Indikator
            </h4>
            <small>Menyimpang &middot; Belum Mencapai &middot; Mencapai &middot; Melampaui</small>
        </div>

<div style="position: relative; height: 260px; width: 100%;">
            <canvas
                id="ikuStatusChart"
                data-labels='<?= json_encode($ikuStatusLabels) ?>'
                data-values='<?= json_encode($ikuStatusData) ?>'>
            </canvas>
        </div>

    </div>

</div>