<?php

header('Content-Type: application/json');

require_once '../../../config/config.php';
require_once '../functions.php';

$repo = new WorkspaceRepository($conn);

$save = $repo->saveRecommendation(

    (int)$_POST['checklist_id'],

    trim($_POST['recommendation'])

);

echo json_encode([

    "success"=>$save

]);