<?php

function siquaPolarPoint($cx, $cy, $r, $angleDeg) {
    $rad = deg2rad($angleDeg);
    return ['x' => $cx + $r * cos($rad), 'y' => $cy - $r * sin($rad)];
}

function siquaArcPath($cx, $cy, $r, $startAngle, $endAngle) {
    $start = siquaPolarPoint($cx, $cy, $r, $startAngle);
    $end = siquaPolarPoint($cx, $cy, $r, $endAngle);
    $largeArc = abs($startAngle - $endAngle) > 180 ? 1 : 0;
    return "M {$start['x']} {$start['y']} A {$r} {$r} 0 {$largeArc} 1 {$end['x']} {$end['y']}";
}

$statusZones = [
    ['from' => 180, 'to' => 120, 'color' => '#ef4444'],
    ['from' => 120, 'to' => 60, 'color' => '#f59e0b'],
    ['from' => 60, 'to' => 0, 'color' => '#22c55e'],
];

$gaugeSize = 260;
$gaugeCx = $gaugeSize / 2;
$gaugeCy = $gaugeSize / 2 + 10;
$gaugeR = ($gaugeSize / 2) - 32;
$gaugeStroke = 40;

$zonesSvg = '';
foreach ($statusZones as $z) {
    $path = siquaArcPath($gaugeCx, $gaugeCy, $gaugeR, $z['from'], $z['to']);
    $zonesSvg .= "<path d=\"{$path}\" fill=\"none\" stroke=\"{$z['color']}\" stroke-width=\"{$gaugeStroke}\" />";
}

$ticksSvg = '';
foreach ([0, 25, 50, 75, 100] as $v) {
    $angle = 180 - ($v / 100) * 180;
    $labelPos = siquaPolarPoint($gaugeCx, $gaugeCy, $gaugeR - $gaugeStroke/2 - 16, $angle);
    $ticksSvg .= "<text x=\"{$labelPos['x']}\" y=\"{$labelPos['y']}\" text-anchor=\"middle\" dominant-baseline=\"middle\" style=\"font-size:9px; fill:#9ca3af; font-weight:600;\">{$v}</text>";
}

$needleAngle = 180 - ($auditCompletionPercent / 100) * 180;
$needleTip = siquaPolarPoint($gaugeCx, $gaugeCy, $gaugeR - $gaugeStroke/2 - 6, $needleAngle);
$needleBack = siquaPolarPoint($gaugeCx, $gaugeCy, 12, $needleAngle + 180);

$needleColor = $auditCompletionPercent >= 75 ? '#22c55e' : ($auditCompletionPercent >= 40 ? '#f59e0b' : '#ef4444');

$statusBadgeMap = ['Draft' => ['#9ca3af', 'Draft'], 'Aktif' => ['#f59e0b', 'Berjalan'], 'Ditutup' => ['#22c55e', 'Selesai']];

?>

<div class="col-lg-5 mb-4">
    <div class="widget h-100 d-flex flex-column">

            <div class="mb-3 text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.8px; font-weight:700;">
                <i class="bi bi-speedometer2"></i> Status Audit &mdash; Penyelesaian Periode
            </div>

            <div class="d-flex flex-column align-items-center">
                <svg width="<?= $gaugeSize ?>" height="<?= $gaugeSize/2 + 50 ?>" viewBox="0 0 <?= $gaugeSize ?> <?= $gaugeSize/2 + 50 ?>">
                    <?= $zonesSvg ?>
                    <?= $ticksSvg ?>
                    <line x1="<?= $needleBack['x'] ?>" y1="<?= $needleBack['y'] ?>" x2="<?= $needleTip['x'] ?>" y2="<?= $needleTip['y'] ?>"
                        stroke="<?= $needleColor ?>" stroke-width="3.5" stroke-linecap="round" />
                    <circle cx="<?= $gaugeCx ?>" cy="<?= $gaugeCy ?>" r="7" fill="<?= $needleColor ?>" stroke="#fff" stroke-width="2" />
                                        <text x="<?= $gaugeCx ?>" y="<?= $gaugeSize/2 + 44 ?>" text-anchor="middle" style="font-size:24px; font-weight:800; fill:#3a3348;"><?= $auditCompletionPercent ?>%</text>
                </svg>
                <div class="text-muted" style="font-size:11px;"><?= $auditSelesai ?> dari <?= $totalAudit ?> Periode Selesai</div>
            </div>

            <div class="mt-3" style="max-height:180px; overflow-y:auto;">
                <?php foreach ($auditPeriodList as $p): ?>
                    <?php $badge = $statusBadgeMap[$p['status']] ?? ['#9ca3af', $p['status']]; ?>
                    <div class="d-flex justify-content-between align-items-center py-1" style="font-size:12px; border-bottom:1px dashed #eee;">
                        <span style="color:#3a3348;"><?= htmlspecialchars($p['period_name']) ?></span>
                        <span class="hud-badge" style="background:<?= $badge[0] ?>1a; color:<?= $badge[0] ?>;"><?= $badge[1] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

    </div>
</div>