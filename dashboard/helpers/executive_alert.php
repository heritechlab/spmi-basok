<?php
/**
 * ============================================
 * SIQUA
 * Executive Alert Engine
 * ============================================
 */

$alerts = [];

/* ============================================
   1. PERIODE AUDIT
============================================ */

$qAudit = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) total
         FROM audit_periods
         WHERE status='Aktif'"
    )
);

if($qAudit['total']==0){

    $alerts[] = [

        'level' => 'danger',

        'icon'  => 'bi-exclamation-circle-fill',

        'title' => 'Audit Belum Dimulai',

        'message' => 'Belum ada periode audit yang aktif.',

        'badge' => 'Perlu Tindakan'

    ];

}else{

    $alerts[] = [

        'level'=>'success',

        'icon'=>'bi-check-circle-fill',

        'title'=>'Audit Aktif',

        'message'=>'Periode audit sedang berjalan.',

        'badge'=>'Baik'

    ];

}

/* ============================================
   2. STANDAR
============================================ */

$qStandar = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total FROM standards"

    )

);

if($qStandar['total']==0){

    $alerts[]=[

        'level'=>'danger',

        'icon'=>'bi-bookmark-x-fill',

        'title'=>'Standar Belum Ada',

        'message'=>'Belum terdapat Standar Mutu.',

        'badge'=>'Kosong'

    ];

}else{

    $alerts[]=[

        'level'=>'success',

        'icon'=>'bi-bookmark-check-fill',

        'title'=>'Standar Lengkap',

        'message'=>$qStandar['total'].' Standar Mutu tersedia.',

        'badge'=>'OK'

    ];

}

/* ============================================
   3. TEMUAN
============================================ */

$qTemuan = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total FROM audit_findings"

    )

);

if($qTemuan['total']==0){

    $alerts[]=[

        'level'=>'primary',

        'icon'=>'bi-search',

        'title'=>'Belum Ada Temuan',

        'message'=>'Belum terdapat temuan audit.',

        'badge'=>'Info'

    ];

}else{

    $alerts[]=[

        'level'=>'warning',

        'icon'=>'bi-exclamation-triangle-fill',

        'title'=>'Temuan Audit',

        'message'=>$qTemuan['total'].' Temuan perlu ditindaklanjuti.',

        'badge'=>'Warning'

    ];

}

/* ============================================
   4. RTL
============================================ */

$qRTL = mysqli_fetch_assoc(

    mysqli_query(

        $conn,

        "SELECT COUNT(*) total FROM corrective_actions"

    )

);

if($qRTL['total']==0){

    $alerts[]=[

        'level'=>'danger',

        'icon'=>'bi-list-check',

        'title'=>'RTL Belum Ada',

        'message'=>'Belum ada Rencana Tindak Lanjut.',

        'badge'=>'Segera'

    ];

}else{

    $alerts[]=[

        'level'=>'success',

        'icon'=>'bi-check2-square',

        'title'=>'RTL Tersedia',

        'message'=>$qRTL['total'].' RTL telah dibuat.',

        'badge'=>'Baik'

    ];

}