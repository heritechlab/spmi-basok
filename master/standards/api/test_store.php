<?php

require_once __DIR__.'/../../../config/config.php';
require_once __DIR__.'/../repository.php';
require_once __DIR__.'/../service.php';

$repository = new StandardRepository($conn);
$service    = new StandardService($repository);

$data = [
    'code'             => 'STD-001',
    'name'             => 'Standar Uji Coba',

    'reference'        => 'Permendiktisaintek',

    'document_number'  => 'DOC-001',
    'publish_date'     => '2026-07-17',

    'type_id'          => 1,
    'category_id'      => 1,
    'status_id'        => 1,

    'category'         => 'Akademik',

    'version'          => '1.0',
    'revision'         => 0,
    'year'             => 2026,

    'weight'           => 1,

    'sort_order'       => 1,

    'description'      => 'Testing',

    'status'           => 1,

    'is_active'        => 1
];

try {

    $service->create($data);

    echo "SUCCESS";

} catch(Throwable $e){

    echo $e->getMessage();

}