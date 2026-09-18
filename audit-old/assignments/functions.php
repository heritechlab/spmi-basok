<?php
/**
 * ==========================================================
 * SIQUA v1.0
 * Sistem Informasi Audit Mutu Internal
 * ----------------------------------------------------------
 * Module : Audit Assignment
 * File   : audit/assignments/functions.php
 * Author : Basok Bukhari
 * ==========================================================
 */

if (!defined('APP_NAME')) {
    exit('No direct script access allowed');
}

/*
|--------------------------------------------------------------------------
| GENERAL HELPER
|--------------------------------------------------------------------------
| Helper umum yang dipakai seluruh Modul Audit Assignment
|--------------------------------------------------------------------------
*/
function clean($value)
{
    global $conn;

    return htmlspecialchars(
        trim(
            $conn->real_escape_string($value)
        ),
        ENT_QUOTES,
        'UTF-8'
    );
}
function formatDate($date)
{
    if (empty($date)) {
        return '-';
    }

    return date('d M Y', strtotime($date));
}
function formatDateTime($date)
{
    if (empty($date)) {
        return '-';
    }

    return date('d M Y H:i', strtotime($date));
}
function formatNumber($number)
{
    return number_format($number, 0, ',', '.');
}
function redirect($url)
{
    header("Location: {$url}");
    exit;
}
function setSuccess($message)
{
    $_SESSION['success'] = $message;
}
function setError($message)
{
    $_SESSION['error'] = $message;
}

function badgeStatus($status)
{
    switch ($status) {

        case 'Draft':
            $class = 'bg-secondary';
            break;

        case 'Dijadwalkan':
            $class = 'bg-info';
            break;

        case 'Berlangsung':
            $class = 'bg-warning text-dark';
            break;

        case 'Selesai':
            $class = 'bg-success';
            break;

        case 'Dibatalkan':
            $class = 'bg-danger';
            break;

        default:
            $class = 'bg-dark';
    }

    return '<span class="badge '.$class.'">'.$status.'</span>';
}

function badgeProgress($progress)
{
    if ($progress >= 100) {
        return 'bg-success';
    }

    if ($progress >= 70) {
        return 'bg-primary';
    }

    if ($progress >= 40) {
        return 'bg-warning';
    }

    return 'bg-danger';
}
function currentUser()
{
    return $_SESSION['user_id'] ?? null;
}
function isLogin()
{
    return isset($_SESSION['user_id']);
}

/*
|--------------------------------------------------------------------------
| NUMBER GENERATOR
|--------------------------------------------------------------------------
*/

/**
 * Generate Nomor Penugasan Audit
 * Format : AMI-2026-0001
 */
function generateAssignmentNumber()
{
    global $conn;

    $year = date('Y');

    $sql = "
        SELECT assignment_number
        FROM audit_assignments
        WHERE assignment_number LIKE 'AMI-$year-%'
        ORDER BY id DESC
        LIMIT 1
    ";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {

        $row = $result->fetch_assoc();

        $last = $row['assignment_number'];

        $number = (int) substr($last, -4);

        $number++;

    } else {

        $number = 1;

    }

    return "AMI-" . $year . "-" . str_pad($number, 4, "0", STR_PAD_LEFT);
}

/*
|--------------------------------------------------------------------------
| AUDIT PERIOD
|--------------------------------------------------------------------------
*/

function getAuditPeriods()
{
    global $conn;

    return $conn->query("
        SELECT *
        FROM audit_periods
        WHERE status='Aktif'
        ORDER BY year DESC,
                 semester ASC
    ");
}

/*
|--------------------------------------------------------------------------
| AUDITEE
|--------------------------------------------------------------------------
*/

function getAuditees()
{
    global $conn;

    return $conn->query("
        SELECT *
        FROM auditees
        WHERE status=1
        ORDER BY name ASC
    ");
}
/*
|--------------------------------------------------------------------------
| AUDITOR
|--------------------------------------------------------------------------
*/

function getAuditors()
{
    global $conn;

    return $conn->query("
        SELECT
            users.id,
            users.full_name,
            roles.role_name
        FROM users

        INNER JOIN roles
            ON roles.id = users.role_id

        WHERE users.status=1

        ORDER BY users.full_name ASC
    ");
}

/*
|--------------------------------------------------------------------------
| COORDINATOR
|--------------------------------------------------------------------------
*/

function getCoordinators()
{
    global $conn;

    return $conn->query("
        SELECT
            id,
            full_name
        FROM users
        WHERE status = 1
        ORDER BY full_name ASC
    ");
}
/*
|--------------------------------------------------------------------------
| USER
|--------------------------------------------------------------------------
*/

function getUsers()
{
    global $conn;

    return $conn->query("
        SELECT id,full_name
        FROM users
        WHERE status=1
        ORDER BY full_name ASC
    ");
}

/*
|--------------------------------------------------------------------------
| TOTAL ASSIGNMENT
|--------------------------------------------------------------------------
*/

function totalAssignment()
{
    global $conn;

    $row = $conn
        ->query("
            SELECT COUNT(*) total
            FROM audit_assignments
        ")
        ->fetch_assoc();

    return $row['total'];
}
function totalDraft()
{
    global $conn;

    $row = $conn
        ->query("
            SELECT COUNT(*) total
            FROM audit_assignments
            WHERE status='Draft'
        ")
        ->fetch_assoc();

    return $row['total'];
}
function totalRunning()
{
    global $conn;

    $row = $conn
        ->query("
            SELECT COUNT(*) total
            FROM audit_assignments
            WHERE status='Berlangsung'
        ")
        ->fetch_assoc();

    return $row['total'];
}

function totalCancelled()
{
    global $conn;

    $row = $conn
        ->query("
            SELECT COUNT(*) total
            FROM audit_assignments
            WHERE status='Dibatalkan'
        ")
        ->fetch_assoc();

    return $row['total'];
}
function totalScheduled()
{
    global $conn;

    $q = $conn->query("
        SELECT COUNT(*) total
        FROM audit_assignments
        WHERE status='Dijadwalkan'
    ");

    return $q->fetch_assoc()['total'];
}
function totalFinished()
{
    global $conn;

    $q = $conn->query("
        SELECT COUNT(*) total
        FROM audit_assignments
        WHERE status='Selesai'
    ");

    return $q->fetch_assoc()['total'];
}
/*
|--------------------------------------------------------------------------
| DASHBOARD SUMMARY
|--------------------------------------------------------------------------
*/

function assignmentStatistic()
{
    return [

        'total'      => totalAssignment(),

        'draft'      => totalDraft(),

        'scheduled'  => totalScheduled(),

        'running'    => totalRunning(),

        'finished'   => totalFinished(),

        'cancelled'  => totalCancelled()

    ];
}

/*
|--------------------------------------------------------------------------
| GET ALL ASSIGNMENTS
|--------------------------------------------------------------------------
*/

function getAssignments($search = '', $status = '')
{
    global $conn;

    $sql = "
SELECT

    aa.id,
    aa.assignment_number,
    aa.audit_code,
    aa.audit_type,
    aa.assignment_date,
    aa.audit_date,
    aa.status,
    aa.progress_percent,
    aa.current_step,
    aa.approved,

    ap.period_name,

    ad.name AS auditee_name,

    u.full_name AS lead_auditor_name

FROM audit_assignments aa

LEFT JOIN audit_periods ap
    ON ap.id = aa.period_id

LEFT JOIN auditees ad
    ON ad.id = aa.auditee_id

LEFT JOIN users u
    ON u.id = aa.lead_auditor

WHERE 1=1
";

    if (!empty($search)) {

        $search = clean($search);

        $sql .= "
            AND (
                aa.assignment_number LIKE '%{$search}%'
                OR ap.period_name LIKE '%{$search}%'
                OR ad.name LIKE '%{$search}%'
            )
        ";
    }

    if (!empty($status)) {

        $status = clean($status);

        $sql .= " AND aa.status='{$status}'";

    }

    $sql .= " ORDER BY aa.assignment_date DESC";

    return $conn->query($sql);
}
/*
|--------------------------------------------------------------------------
| GET ASSIGNMENT
|--------------------------------------------------------------------------
*/

function getAssignment($id)
{
    global $conn;

    $id = (int)$id;

    $sql = "
        SELECT
            aa.*,
            ap.period_name,
            ad.name AS auditee_name,
            u.full_name AS lead_name
        FROM audit_assignments aa

        LEFT JOIN audit_periods ap
            ON ap.id=aa.period_id

        LEFT JOIN auditees ad
            ON ad.id=aa.auditee_id

        LEFT JOIN users u
            ON u.id=aa.lead_auditor

        WHERE aa.id={$id}

        LIMIT 1
    ";

    return $conn->query($sql)->fetch_assoc();
}
/*
|--------------------------------------------------------------------------
| CHECK ASSIGNMENT
|--------------------------------------------------------------------------
*/

function assignmentExists($assignmentNumber)
{
    global $conn;

    $assignmentNumber = clean($assignmentNumber);

    $sql = "
        SELECT id
        FROM audit_assignments
        WHERE assignment_number='{$assignmentNumber}'
        LIMIT 1
    ";

    return $conn->query($sql)->num_rows > 0;
}
/*
|--------------------------------------------------------------------------
| CREATE ASSIGNMENT
|--------------------------------------------------------------------------
*/

function createAssignment($data)
{
    global $conn;

    $period_id          = (int)$data['period_id'];
    $auditee_id         = (int)$data['auditee_id'];
    $lead_auditor       = (int)$data['lead_auditor'];
    $coordinator        = (int)$data['coordinator'];

    $assignment_number  = clean($data['assignment_number']);
    $audit_code         = clean($data['audit_code']);
    $audit_type         = clean($data['audit_type']);
    $scope              = clean($data['scope']);
    $objective      = clean($data['objective']);
    $criteria       = clean($data['criteria']);
    $notes          = clean($data['notes']);
    $risk_level     = clean($data['risk_level']);
    $audit_location = clean($data['audit_location']);
    $audit_method   = clean($data['audit_method']);
    $audit_start    = $data['audit_start'];
    $audit_finish   = $data['audit_finish'];

    $assignment_date    = $data['assignment_date'];
    $audit_date         = $data['audit_date'];

    $created_by         = currentUser();

    /*
    |------------------------------------------------------------
    | VALIDASI
    |------------------------------------------------------------
    */

    if ($period_id <= 0) {
        return [
            'status' => false,
            'message' => 'Periode Audit belum dipilih.'
        ];
    }

    if ($auditee_id <= 0) {
        return [
            'status' => false,
            'message' => 'Auditee belum dipilih.'
        ];
    }

    if ($lead_auditor <= 0) {
        return [
            'status' => false,
            'message' => 'Lead Auditor belum dipilih.'
        ];
    }

    if ($assignment_number == '') {
        return [
            'status' => false,
            'message' => 'Nomor Penugasan kosong.'
        ];
    }

    if (assignmentExists($assignment_number)) {
        return [
            'status' => false,
            'message' => 'Nomor Penugasan sudah digunakan.'
        ];
    }
    $status = isset($_POST['publish'])
            ? 'Dijadwalkan'
            : 'Draft';

    $conn->begin_transaction();

    try {

        $sql = "
            INSERT INTO audit_assignments
            (
                period_id,
                auditee_id,
                lead_auditor,
                coordinator,

                assignment_number,
                audit_code,
                audit_type,

                scope,
                objective,
                criteria,

                risk_level,

                audit_location,
                audit_method,

                assignment_date,
                audit_date,

                audit_start,
                audit_finish,

                notes,

                status,
                progress_percent,

                created_by
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                ?,

                ?,
                ?,
                ?,

                ?,

                ?,
                ?,

                ?,
                ?,

                ?,
                ?,

                ?,

                ?,

                0,

                ?
            )
                ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(

                "iiiisssssssssssssssi",

                $period_id,
                $auditee_id,
                $lead_auditor,
                $coordinator,

                $assignment_number,
                $audit_code,
                $audit_type,

                $scope,
                $objective,
                $criteria,

                $risk_level,

                $audit_location,
                $audit_method,

                $assignment_date,
                $audit_date,

                $audit_start,
                $audit_finish,

                $notes,

                $status,

                $created_by

                );

        $stmt->execute();

        $assignment_id = $conn->insert_id;

                      /*
        |------------------------------------------------------------
        | Tambahkan Lead Auditor sebagai Ketua Tim
        |------------------------------------------------------------
        */

        $sql = "
            INSERT INTO audit_team_members
            (
                assignment_id,
                user_id,
                role_in_team,
                status,
                joined_at,
                created_by
            )
            VALUES
            (
                ?,
                ?,
                'Ketua',
                'Aktif',
                NOW(),
                ?
            )
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iii",
            $assignment_id,
            $lead_auditor,
            $created_by
        );

        $stmt->execute();

                /*
        |------------------------------------------------------------
        | Simpan Activity Log
        |------------------------------------------------------------
        */

        logActivity(
    $created_by,
    'Audit Assignment',
    'Membuat Penugasan Audit : '.$assignment_number
);


                /*
        |------------------------------------------------------------
        | Commit Transaction
        |------------------------------------------------------------
        */

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Penugasan Audit berhasil dibuat.'
        ];

    } catch (Exception $e) {

        $conn->rollback();

        return [
            'status' => false,
            'message' => $e->getMessage()
        ];

    }

}
/*
|--------------------------------------------------------------------------
| UPDATE ASSIGNMENT
|--------------------------------------------------------------------------
*/

function updateAssignment($id, $data)
{    global $conn;

    $id = (int)$id;

    $period_id          = (int)$data['period_id'];
    $auditee_id         = (int)$data['auditee_id'];
    $lead_auditor       = (int)$data['lead_auditor'];
    $coordinator        = (int)$data['coordinator'];

    $assignment_number  = clean($data['assignment_number']);
    $audit_code         = clean($data['audit_code']);
    $audit_type         = clean($data['audit_type']);
    $scope              = clean($data['scope']);

    $assignment_date    = $data['assignment_date'];
    $audit_date         = $data['audit_date'];

    $updated_by = currentUser();
        if ($id <= 0) {

        return [
            'status'=>false,
            'message'=>'Data tidak ditemukan.'
        ];

    }
    $status = isset($_POST['publish'])
    ? 'Dijadwalkan'
    : 'Draft';

    $conn->begin_transaction();

    try{
                $sql = "
            UPDATE audit_assignments
            SET
                period_id = ?,
                auditee_id = ?,
                lead_auditor = ?,
                coordinator = ?,

                assignment_number = ?,
                audit_code = ?,
                audit_type = ?,
                scope = ?,

                assignment_date = ?,
                audit_date = ?,

                updated_by = ?

            WHERE id = ?
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iiiissssssii",
            $period_id,
            $auditee_id,
            $lead_auditor,
            $coordinator,
            $assignment_number,
            $audit_code,
            $audit_type,
            $scope,
            $assignment_date,
            $audit_date,
            $updated_by,
            $id
        );

        $stmt->execute();

                /*
        |------------------------------------------------------------
        | Update Ketua Tim Audit
        |------------------------------------------------------------
        */

        $sql = "
            UPDATE audit_team_members
            SET
                user_id = ?,
                updated_by = ?
            WHERE assignment_id = ?
            AND role_in_team = 'Ketua'
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iii",
            $lead_auditor,
            $updated_by,
            $id
        );

        $stmt->execute();
                /*
        |------------------------------------------------------------
        | Activity Log
        |------------------------------------------------------------
        */

       logActivity(
    $updated_by,
    'Audit Assignment',
    'Mengubah Penugasan Audit : '.$assignment_number
);
                /*
        |------------------------------------------------------------
        | Commit
        |------------------------------------------------------------
        */

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Data Penugasan Audit berhasil diperbarui.'
        ];

    } catch (Exception $e) {

        $conn->rollback();

        return [
            'status' => false,
            'message' => $e->getMessage()
        ];

    }

}

/*
|--------------------------------------------------------------------------
| DELETE ASSIGNMENT
|--------------------------------------------------------------------------
*/

function deleteAssignment($id)
{
    global $conn;

    $id = (int)$id;

    $deleted_by = currentUser();

    $conn->begin_transaction();

    try {

        // Ambil data assignment untuk log
        $assignment = getAssignment($id);

        if (!$assignment) {
            return [
                'status' => false,
                'message' => 'Data penugasan tidak ditemukan.'
            ];
        }

        // Hapus anggota tim audit
        $stmt = $conn->prepare("
            DELETE FROM audit_team_members
            WHERE assignment_id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Hapus penugasan audit
        $stmt = $conn->prepare("
            DELETE FROM audit_assignments
            WHERE id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Simpan log aktivitas
       logActivity(
    $deleted_by,
    'Audit Assignment',
    'Menghapus Penugasan Audit : '.$assignment['assignment_number']
);

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Penugasan Audit berhasil dihapus.'
        ];

    } catch (Exception $e) {

        $conn->rollback();

        return [
            'status' => false,
            'message' => $e->getMessage()
        ];

    }
}
/*
|--------------------------------------------------------------------------
| SCHEDULE ASSIGNMENT
|--------------------------------------------------------------------------
*/

function scheduleAssignment($id, $data)
{
    global $conn;

    $id = (int)$id;

    $audit_start = $data['audit_start'];
    $audit_finish = $data['audit_finish'];

    $opening_meeting = $data['opening_meeting'];
    $closing_meeting = $data['closing_meeting'];

    $audit_location = clean($data['audit_location']);
    $audit_method = clean($data['audit_method']);

    $updated_by = currentUser();

    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare("
            UPDATE audit_assignments
            SET
                audit_start = ?,
                audit_finish = ?,
                opening_meeting = ?,
                closing_meeting = ?,
                audit_location = ?,
                audit_method = ?,
                status = 'Dijadwalkan',
                progress_percent = 15,
                current_step = 'Penjadwalan',
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssssssii",
            $audit_start,
            $audit_finish,
            $opening_meeting,
            $closing_meeting,
            $audit_location,
            $audit_method,
            $updated_by,
            $id
        );

        $stmt->execute();

       logActivity(
    $updated_by,
    'Audit Assignment',
    'Menjadwalkan Audit'
);

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Jadwal audit berhasil disimpan.'
        ];

    } catch (Exception $e) {

        $conn->rollback();

        return [
            'status' => false,
            'message' => $e->getMessage()
        ];

    }
}
/*
|--------------------------------------------------------------------------
| START ASSIGNMENT
|--------------------------------------------------------------------------
*/

function startAssignment($id)
{
    global $conn;

    $id = (int)$id;

    $updated_by = currentUser();

    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare("
            UPDATE audit_assignments
            SET
                status = 'Berlangsung',
                progress_percent = 25,
                current_step = 'Audit Lapangan',
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param("ii", $updated_by, $id);

        $stmt->execute();

        logActivity(
    $updated_by,
    'Audit Assignment',
    'Memulai Audit Lapangan'
);

        $conn->commit();

        return [
            'status' => true,
            'message' => 'Audit berhasil dimulai.'
        ];

    } catch (Exception $e) {

        $conn->rollback();

        return [
            'status' => false,
            'message' => $e->getMessage()
        ];

    }
}

/*
|--------------------------------------------------------------------------
| APPROVE ASSIGNMENT
|--------------------------------------------------------------------------
*/

function approveAssignment($id)
{
    global $conn;

    $id = (int)$id;

    $approved_by = currentUser();

    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare("
            UPDATE audit_assignments
            SET
                approved = 1,
                approved_by = ?,
                approved_at = NOW(),
                status = 'Menunggu Approval',
                progress_percent = 90,
                current_step = 'Approval',
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            "iii",
            $approved_by,
            $approved_by,
            $id
        );

        $stmt->execute();

        logActivity(
    $approved_by,
    'Audit Assignment',
    'Menyetujui Penugasan Audit'
);

        $conn->commit();

        return [
            'status'=>true,
            'message'=>'Approval berhasil.'
        ];

    } catch(Exception $e){

        $conn->rollback();

        return[
            'status'=>false,
            'message'=>$e->getMessage()
        ];

    }

}
/*
|--------------------------------------------------------------------------
| FINISH ASSIGNMENT
|--------------------------------------------------------------------------
*/

function finishAssignment($id)
{
    global $conn;

    $id = (int)$id;

    $updated_by = currentUser();

    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare("
            UPDATE audit_assignments
            SET
                status='Selesai',
                progress_percent=100,
                current_step='Selesai',
                completion_date=NOW(),
                updated_by=?,
                updated_at=NOW()
            WHERE id=?
        ");

        $stmt->bind_param(
            "ii",
            $updated_by,
            $id
        );

        $stmt->execute();

        logActivity(
            $updated_by,
            'Audit Assignment',
            'Menyelesaikan Audit'
        );

        $conn->commit();

        return [
            'status'=>true,
            'message'=>'Audit berhasil diselesaikan.'
        ];

    } catch(Exception $e){

        $conn->rollback();

        return[
            'status'=>false,
            'message'=>$e->getMessage()
        ];

    }

}
/*
|--------------------------------------------------------------------------
| CANCEL ASSIGNMENT
|--------------------------------------------------------------------------
*/

function cancelAssignment($id)
{
    global $conn;

    $id=(int)$id;

    $updated_by=currentUser();

    $stmt=$conn->prepare("
        UPDATE audit_assignments
        SET
            status='Dibatalkan',
            updated_by=?,
            updated_at=NOW()
        WHERE id=?
    ");

    $stmt->bind_param(
        "ii",
        $updated_by,
        $id
    );

    $result=$stmt->execute();

    if($result){

        logActivity(
            $updated_by,
            'Audit Assignment',
            'Membatalkan Penugasan Audit'
        );

    }

    return $result;

}
/*
|--------------------------------------------------------------------------
| GET ASSIGNMENT TEAM
|--------------------------------------------------------------------------
*/

function getAssignmentTeam($assignment_id)
{
    global $conn;

    $assignment_id=(int)$assignment_id;

    return $conn->query("
        SELECT

            atm.*,

            users.full_name

        FROM audit_team_members atm

        INNER JOIN users

            ON users.id=atm.user_id

        WHERE assignment_id=$assignment_id

        ORDER BY role_in_team ASC
    ");

}
/*
|--------------------------------------------------------------------------
| ADD TEAM MEMBER
|--------------------------------------------------------------------------
*/

function addTeamMember(
    $assignment_id,
    $user_id,
    $role
){

    global $conn;

    $created_by=currentUser();

    $stmt=$conn->prepare("
        INSERT INTO audit_team_members
        (
            assignment_id,
            user_id,
            role_in_team,
            status,
            joined_at,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            'Aktif',
            NOW(),
            ?
        )
    ");

    $stmt->bind_param(
        "iisi",
        $assignment_id,
        $user_id,
        $role,
        $created_by
    );

    $result=$stmt->execute();

    if($result){

        logActivity(
            $created_by,
            'Audit Assignment',
            'Menambah Anggota Tim Audit'
        );

    }

    return $result;

}
/*
|--------------------------------------------------------------------------
| REMOVE TEAM MEMBER
|--------------------------------------------------------------------------
*/

function removeTeamMember($id)
{

    global $conn;

    $stmt=$conn->prepare("
        DELETE FROM audit_team_members
        WHERE id=?
    ");

    $stmt->bind_param(
        "i",
        $id
    );

    return $stmt->execute();

}
/*
|--------------------------------------------------------------------------
| SEARCH ASSIGNMENTS
|--------------------------------------------------------------------------
*/

function searchAssignments($keyword = '', $status = '')
{
    global $conn;

    $keyword = clean($keyword);
    $status  = clean($status);

    $sql = "
        SELECT
            aa.*,
            ap.period_name,
            ad.name AS auditee_name,
            u.full_name AS lead_auditor_name

        FROM audit_assignments aa

        LEFT JOIN audit_periods ap
            ON ap.id = aa.period_id

        LEFT JOIN auditees ad
            ON ad.id = aa.auditee_id

        LEFT JOIN users u
            ON u.id = aa.lead_auditor

        WHERE 1=1
    ";

    if ($keyword != '') {

        $sql .= "
            AND (
                aa.assignment_number LIKE '%{$keyword}%'
                OR aa.audit_code LIKE '%{$keyword}%'
                OR ad.name LIKE '%{$keyword}%'
                OR u.full_name LIKE '%{$keyword}%'
            )
        ";

    }

    if ($status != '') {

        $sql .= "
            AND aa.status='{$status}'
        ";

    }

    $sql .= "
        ORDER BY aa.assignment_date DESC
    ";

    return $conn->query($sql);

}

/*
|--------------------------------------------------------------------------
| VALIDATE ASSIGNMENT
|--------------------------------------------------------------------------
*/

function validateAssignment($data)
{

    $error=[];

    if(empty($data['period_id']))
        $error[]='Periode Audit wajib dipilih';

    if(empty($data['auditee_id']))
        $error[]='Auditee wajib dipilih';

    if(empty($data['lead_auditor']))
        $error[]='Lead Auditor wajib dipilih';

    if(empty($data['assignment_number']))
        $error[]='Nomor Assignment wajib diisi';

    if(empty($data['audit_type']))
        $error[]='Jenis Audit wajib dipilih';

    if(empty($data['assignment_date']))
        $error[]='Tanggal Penugasan wajib diisi';

    if(empty($data['audit_date']))
        $error[]='Tanggal Audit wajib diisi';

    return $error;

}
/*
|--------------------------------------------------------------------------
| ASSIGNMENT STATUS
|--------------------------------------------------------------------------
*/

function getAssignmentStatus()
{

    return [

        'Draft',

        'Dijadwalkan',

        'Berlangsung',

        'Menunggu Approval',

        'Selesai',

        'Dibatalkan'

    ];

}
/*
|--------------------------------------------------------------------------
| AUDIT TYPE
|--------------------------------------------------------------------------
*/

function getAuditTypes()
{

    return [

        'Audit Internal',

        'Audit Lapangan',

        'Audit Dokumen',

        'Audit Tindak Lanjut'

    ];

}
/*
|--------------------------------------------------------------------------
| AUDIT METHOD
|--------------------------------------------------------------------------
*/

function getAuditMethods()
{

    return [

        'Luring',

        'Daring',

        'Hybrid'

    ];

}

/*
|--------------------------------------------------------------------------
| DETAIL ASSIGNMENT
|--------------------------------------------------------------------------
*/

function getAssignmentDetail($id)
{
    global $conn;

    $id = (int)$id;

    $sql = "
    SELECT

        aa.*,

        ap.period_name,

        ad.name AS auditee_name,

        lead.full_name AS lead_auditor_name,

        coordinator.full_name AS coordinator_name

    FROM audit_assignments aa

    LEFT JOIN audit_periods ap
        ON ap.id = aa.period_id

    LEFT JOIN auditees ad
        ON ad.id = aa.auditee_id

    LEFT JOIN users lead
        ON lead.id = aa.lead_auditor

    LEFT JOIN users coordinator
        ON coordinator.id = aa.coordinator

    WHERE aa.id = {$id}

    LIMIT 1
    ";

    $result = $conn->query($sql);

    if(!$result){
        return false;
    }

    if($result->num_rows==0){
        return false;
    }

    return $result->fetch_assoc();
}
/*
|--------------------------------------------------------------------------
| ACTIVITY LOG
|--------------------------------------------------------------------------
*/

function logActivity($user_id, $module, $activity)
{
    global $conn;

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO activity_logs
        (
            user_id,
            module,
            activity,
            ip_address,
            browser
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->bind_param(
        "issss",
        $user_id,
        $module,
        $activity,
        $ip,
        $browser
    );

    return $stmt->execute();
}

