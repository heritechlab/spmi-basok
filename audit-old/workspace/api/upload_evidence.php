<?php

header('Content-Type: application/json');

require_once '../../../config/config.php';
require_once '../functions.php';

if(empty($_FILES['file'])){

exit(json_encode([

"success"=>false

]));

}

$file=$_FILES['file'];

$name=time()."_".$file['name'];

move_uploaded_file(

$file['tmp_name'],

UPLOAD_PATH.$name

);

$repo=new WorkspaceRepository($conn);

$repo->saveEvidence(

(int)$_POST['checklist_id'],

$name

);

echo json_encode([

"success"=>true,

"file"=>$name

]);