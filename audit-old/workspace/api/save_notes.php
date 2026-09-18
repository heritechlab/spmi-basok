<?php

header('Content-Type: application/json');

require_once '../../../config/config.php';
require_once '../functions.php';

$repo=new WorkspaceRepository($conn);

$save=$repo->saveNotes(

(int)$_POST['checklist_id'],

trim($_POST['notes'])

);

echo json_encode([

"success"=>$save

]);