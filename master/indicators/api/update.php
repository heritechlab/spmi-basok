<?php

declare(strict_types=1);

if(session_status()===PHP_SESSION_NONE){

    session_start();

}

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__.'/../../../config/config.php';
require_once __DIR__.'/../../../core/Auth.php';
require_once __DIR__.'/../repository.php';
require_once __DIR__.'/../service.php';

try{

    if (!Auth::canManage()) {
        echo json_encode([
            'success' => false,
            'message' => 'Anda tidak memiliki akses untuk mengubah data.'
        ]);
        exit;
    }

    $repository=new IndicatorRepository($conn);

    $service=new IndicatorService($repository);

    echo json_encode(

        $service->update(

            (int)$_POST['id'],

            $_POST

        )

    );

}catch(Throwable $e){

    echo json_encode([

        'success'=>false,

        'message'=>$e->getMessage()

    ]);

}