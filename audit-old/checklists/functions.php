<?php

require_once '../../config/config.php';

function getAssignmentDetail($id)
{
    global $conn;

    $id = (int)$id;

    $sql = "
        SELECT
            aa.*,
            ap.period_name,
            ad.name AS auditee_name
        FROM audit_assignments aa
        LEFT JOIN audit_periods ap
            ON ap.id = aa.period_id
        LEFT JOIN auditees ad
            ON ad.id = aa.auditee_id
        WHERE aa.id = {$id}
        LIMIT 1
    ";

    return $conn->query($sql)->fetch_assoc();
}
/*
|--------------------------------------------------------------------------
| STANDAR CHECKLIST
|--------------------------------------------------------------------------
*/

function getChecklistStandards($assignment_id)
{
    return [

        [
            'id' => 1,
            'code' => 'STD-01',
            'name' => 'Standar Visi Misi',
            'indicator' => 12,
            'completed' => 8,
            'progress' => 67
        ],

        [
            'id' => 2,
            'code' => 'STD-02',
            'name' => 'Standar Tata Pamong',
            'indicator' => 18,
            'completed' => 11,
            'progress' => 61
        ]

    ];
}