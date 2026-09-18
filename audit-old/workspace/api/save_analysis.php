<?php

header('Content-Type: application/json');

require_once '../../../config/config.php';
require_once '../functions.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    exit(json_encode([
        "success"=>false,
        "message"=>"Invalid Request"
    ]));
}

$checklist = (int)($_POST['checklist_id'] ?? 0);

$analysis = trim($_POST['analysis'] ?? '');

$type = trim($_POST['type'] ?? 'root');

$repo = new WorkspaceRepository($conn);

if($type=="support"){

    $save = $repo->saveSupportingFactor(
        $checklist,
        $analysis
    );

}else{

    $save = $repo->saveRootCause(
        $checklist,
        $analysis
    );

}

echo json_encode([
    "success"=>$save,
    "message"=>$save
        ? "Analisis berhasil disimpan."
        : "Gagal menyimpan analisis."
]);