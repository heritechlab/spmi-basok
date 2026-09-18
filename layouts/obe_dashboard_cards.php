<?php
// Partial ini di-include di halaman: dashboard_pelaporan, penilaian, laporan_cpl, ketercapaian_mahasiswa, pelaporan_siakad
// Variabel $obeActiveCard wajib diisi SEBELUM include ini, nilai salah satu: 'mahasiswa','mk','prodi','siakad'
$obeActiveCard = $obeActiveCard ?? '';
?>
<style>
    .obe-cards-strip { display: flex; gap: 14px; margin-bottom: 22px; }
    .obe-nav-card {
        flex: 1; border-radius: 16px; padding: 18px 20px; text-decoration: none;
        display: flex; align-items: center; gap: 14px; position: relative; overflow: hidden;
        transition: all .2s cubic-bezier(.4,0,.2,1); box-shadow: 0 4px 14px rgba(20,17,43,0.10);
    }
    .obe-nav-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(20,17,43,0.20); }
    .obe-nav-card.active { box-shadow: 0 0 0 3px rgba(255,255,255,0.6), 0 12px 28px rgba(20,17,43,0.22); transform: translateY(-3px); }

    .obe-nav-card::after {
        content: ""; position: absolute; top: -30%; right: -20%; width: 140px; height: 140px;
        border-radius: 50%; background: rgba(255,255,255,0.12); pointer-events: none;
    }

    .obe-nav-card .icon-box {
        width: 46px; height: 46px; border-radius: 12px; background: rgba(255,255,255,0.22);
        color: #fff; display: flex; align-items: center; justify-content: center; font-size: 21px;
        flex-shrink: 0; position: relative; z-index: 1;
    }
    .obe-nav-card .card-label { font-size: 12.5px; font-weight: 700; color: #fff; line-height: 1.3; position: relative; z-index: 1; }
    .obe-nav-card .card-sub { font-size: 10.5px; color: rgba(255,255,255,0.78); position: relative; z-index: 1; }

    .obe-nav-card.card-mahasiswa { background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%); }
    .obe-nav-card.card-mk        { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); }
    .obe-nav-card.card-prodi     { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
    .obe-nav-card.card-siakad    { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); }
</style>

<div class="obe-cards-strip">
    <a href="<?= BASE_URL ?>obe/ketercapaian_mahasiswa/" class="obe-nav-card card-mahasiswa <?= $obeActiveCard === 'mahasiswa' ? 'active' : '' ?>">
        <div class="icon-box"><i class="bi bi-person-check-fill"></i></div>
        <div>
            <div class="card-label">Ketercapaian CPL Mahasiswa</div>
            <div class="card-sub">Rekap per Mahasiswa</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>obe/penilaian/" class="obe-nav-card card-mk <?= $obeActiveCard === 'mk' ? 'active' : '' ?>">
        <div class="icon-box"><i class="bi bi-journal-check"></i></div>
        <div>
            <div class="card-label">Ketercapaian CPL Mata Kuliah</div>
            <div class="card-sub">Rekap per Mata Kuliah</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>obe/laporan_cpl/" class="obe-nav-card card-prodi <?= $obeActiveCard === 'prodi' ? 'active' : '' ?>">
        <div class="icon-box"><i class="bi bi-building-check"></i></div>
        <div>
            <div class="card-label">Ketercapaian CPL Program Studi</div>
            <div class="card-sub">Rekap tingkat Prodi</div>
        </div>
    </a>
    <a href="<?= BASE_URL ?>obe/pelaporan_siakad/" class="obe-nav-card card-siakad <?= $obeActiveCard === 'siakad' ? 'active' : '' ?>">
        <div class="icon-box"><i class="bi bi-file-earmark-spreadsheet-fill"></i></div>
        <div>
            <div class="card-label">Pelaporan Nilai SIAKAD</div>
            <div class="card-sub">Sikap, Pengetahuan, KU, KK</div>
        </div>
    </a>
</div>