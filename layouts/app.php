<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Periode.php';

if (Auth::isMahasiswa()) {

    $isGatePage = str_contains($_SERVER['REQUEST_URI'], 'survey_gate.php');

    if (!$isGatePage) {

        $activeSurveys = $conn->query("SELECT survey_slug FROM survey_gate_settings WHERE enabled = 1")->fetch_all(MYSQLI_ASSOC);

        if (!empty($activeSurveys)) {

            $mahasiswaIdGateCheck = Auth::getMahasiswaId();
            $stmtNimGate = $conn->prepare("SELECT nim FROM obe_mahasiswa WHERE id = ? LIMIT 1");
            $stmtNimGate->bind_param("i", $mahasiswaIdGateCheck);
            $stmtNimGate->execute();
            $nimGate = $stmtNimGate->get_result()->fetch_assoc()['nim'] ?? null;

            $adaBelumSelesai = false;

            foreach ($activeSurveys as $sv) {
                $stmtTypeGate = $conn->prepare("SELECT id FROM survey_types WHERE slug = ? LIMIT 1");
                $stmtTypeGate->bind_param("s", $sv['survey_slug']);
                $stmtTypeGate->execute();
                $typeRowGate = $stmtTypeGate->get_result()->fetch_assoc();

                if (!$typeRowGate || !$nimGate) {
                    $adaBelumSelesai = true;
                    break;
                }

                $stmtRespGate = $conn->prepare("SELECT id FROM survey_responses WHERE type_id = ? AND nim_pengisi = ? AND survey_year = YEAR(NOW()) LIMIT 1");
                $stmtRespGate->bind_param("is", $typeRowGate['id'], $nimGate);
                $stmtRespGate->execute();

                if (!$stmtRespGate->get_result()->fetch_assoc()) {
                    $adaBelumSelesai = true;
                    break;
                }
            }

            if ($adaBelumSelesai) {
                header("Location: " . BASE_URL . "auth/survey_gate.php");
                exit;
            }
        }
    }
}

require_once __DIR__.'/header.php';

?>

<div class="app-wrapper">

    <?php require_once __DIR__.'/sidebar.php'; ?>

    <div class="main">

        <?php require_once __DIR__.'/navbar.php'; ?>

        <main class="main-content">