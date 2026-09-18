<?php

require_once '../../config/config.php';

/*
|--------------------------------------------------------------------------
| AUDIT WORKSPACE REPOSITORY
|--------------------------------------------------------------------------
*/

class WorkspaceRepository
{

    private mysqli $db;

    public function __construct(mysqli $connection)
    {
        $this->db = $connection;
    }

    /*
|--------------------------------------------------------------------------
| Current Indicator
|--------------------------------------------------------------------------
*/

public function getCurrentIndicator(
    int $assignmentId,
    int $standardId
): ?array
{

    $sql = "

    SELECT

        ac.id checklist_id,

        ac.order_number,

        si.id indicator_id,

        si.item_code,

        si.statement,

        si.target,

        si.unit,

        si.benchmark_value,

        si.verification_method,

        s.id standard_id,

        s.code standard_code,

        s.name standard_name,

        s.weight

    FROM audit_checklists ac

    INNER JOIN audit_indicators ai
        ON ai.id = ac.indicator_id

    INNER JOIN standards s
        ON s.id = si.standard_id

    WHERE
        ac.assignment_id = ?
    AND
        s.id = ?

    ORDER BY ac.order_number

    LIMIT 1

    ";

    $stmt = $this->db->prepare($sql);

    $stmt->bind_param(
        "ii",
        $assignmentId,
        $standardId
    );

    $stmt->execute();

    return $stmt
            ->get_result()
            ->fetch_assoc();

}

/*
|--------------------------------------------------------------------------
| Save Status
|--------------------------------------------------------------------------
*/

public function saveStatus(
    int $checklistId,
    string $status
): bool
{

    $result = $this->getResult($checklistId);

    if($result){

        $sql="

            UPDATE audit_checklist_results

            SET

                audit_status=?,

                updated_at=NOW()

            WHERE checklist_id=?

        ";

        $stmt=$this->db->prepare($sql);

        $stmt->bind_param(

            "si",

            $status,

            $checklistId

        );

    }else{

        $sql="

            INSERT INTO audit_checklist_results(

                checklist_id,

                audit_status,

                created_at

            )

            VALUES(

                ?,

                ?,

                NOW()

            )

        ";

        $stmt=$this->db->prepare($sql);

        $stmt->bind_param(

            "is",

            $checklistId,

            $status

        );

    }

    return $stmt->execute();

}

/*
|--------------------------------------------------------------------------
| Save Finding
|--------------------------------------------------------------------------
*/

public function saveFinding(

    int $checklistId,

    string $finding

):bool
{

    $sql="

    UPDATE audit_checklist_results

    SET

        finding=?,

        updated_at=NOW()

    WHERE checklist_id=?

    ";

    $stmt=$this->db->prepare($sql);

    $stmt->bind_param(

        "si",

        $finding,

        $checklistId

    );

    return $stmt->execute();

}

/*
|--------------------------------------------------------------------------
| Save Root Cause
|--------------------------------------------------------------------------
*/

public function saveRootCause(

    int $checklistId,

    string $rootCause

):bool
{

    $sql="

    UPDATE audit_checklist_results

    SET

        root_cause=?,

        updated_at=NOW()

    WHERE checklist_id=?

    ";

    $stmt=$this->db->prepare($sql);

    $stmt->bind_param(

        "si",

        $rootCause,

        $checklistId

    );

    return $stmt->execute();

}

/*
|--------------------------------------------------------------------------
| Save Supporting Factor
|--------------------------------------------------------------------------
*/

public function saveSupportingFactor(

    int $checklistId,

    string $support

):bool
{

    $sql="

    UPDATE audit_checklist_results

    SET

        supporting_factor=?,

        updated_at=NOW()

    WHERE checklist_id=?

    ";

    $stmt=$this->db->prepare($sql);

    $stmt->bind_param(

        "si",

        $support,

        $checklistId

    );

    return $stmt->execute();

}

/*
|--------------------------------------------------------------------------
| Save Recommendation
|--------------------------------------------------------------------------
*/

public function saveRecommendation(

    int $checklistId,

    string $recommendation

):bool
{

    $sql="

    UPDATE audit_checklist_results

    SET

        recommendation=?,

        updated_at=NOW()

    WHERE checklist_id=?

    ";

    $stmt=$this->db->prepare($sql);

    $stmt->bind_param(

        "si",

        $recommendation,

        $checklistId

    );

    return $stmt->execute();

}

/*
|--------------------------------------------------------------------------
| Save Notes
|--------------------------------------------------------------------------
*/

public function saveNotes(

    int $checklistId,

    string $notes

):bool
{

    $sql="

    UPDATE audit_checklist_results

    SET

        notes=?,

        updated_at=NOW()

    WHERE checklist_id=?

    ";

    $stmt=$this->db->prepare($sql);

    $stmt->bind_param(

        "si",

        $notes,

        $checklistId

    );

    return $stmt->execute();

}

/*
|--------------------------------------------------------------------------
| Save Evidence
|--------------------------------------------------------------------------
*/

public function saveEvidence(

    int $checklistId,

    string $file

):bool
{

    $sql="

    UPDATE audit_checklist_results

    SET

        evidence=?,

        updated_at=NOW()

    WHERE checklist_id=?

    ";

    $stmt=$this->db->prepare($sql);

    $stmt->bind_param(

        "si",

        $file,

        $checklistId

    );

    return $stmt->execute();

}

    /*
    |--------------------------------------------------------------------------
    | Assignment
    |--------------------------------------------------------------------------
    */

    public function getAssignment(int $assignmentId): ?array
    {
        $sql = "
            SELECT
                aa.*,
                au.audit_name,
                u.full_name auditor_name
            FROM audit_assignments aa
            LEFT JOIN audit_units au
                   ON au.id = aa.audit_unit_id
            LEFT JOIN users u
                   ON u.id = aa.auditor_id
            WHERE aa.id=?
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bind_param("i",$assignmentId);

        $stmt->execute();

        $result=$stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

        /*
    |--------------------------------------------------------------------------
    | Navigator
    |--------------------------------------------------------------------------
    */

    public function getNavigator(int $assignmentId):array
    {

        $sql="

        SELECT

            s.id,

            s.code,

            s.name,

            s.weight,

            COUNT(DISTINCT si.id) total_indicator,

            COUNT(DISTINCT ac.id) total_checklist

        FROM standards s

        LEFT JOIN audit_indicators ai

               ON ai.standard_id=s.id

        LEFT JOIN audit_checklists ac

               ON ac.standard_id=s.id

              AND ac.assignment_id=?

        GROUP BY s.id

        ORDER BY s.sort_order

        ";

        $stmt=$this->db->prepare($sql);

        $stmt->bind_param("i",$assignmentId);

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    }

        /*
    |--------------------------------------------------------------------------
    | Indicator
    |--------------------------------------------------------------------------
    */

    public function getIndicator(int $checklistId):?array
    {

        $sql="

        SELECT

            ac.id checklist_id,

            si.id indicator_id,

            si.item_code,

            si.statement,

            si.target,

            si.unit,

            si.benchmark_value,

            si.verification_method,

            s.code standard_code,

            s.name standard_name,

            s.weight

        FROM audit_checklists ac

        JOIN audit_indicators ai

             ON ai.id=ac.indicator_id

        JOIN standards s

             ON s.id=si.standard_id

        WHERE ac.id=?

        LIMIT 1

        ";

        $stmt=$this->db->prepare($sql);

        $stmt->bind_param("i",$checklistId);

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();

    }

        /*
    |--------------------------------------------------------------------------
    | Result
    |--------------------------------------------------------------------------
    */

    public function getResult(int $checklistId):?array
    {

        $sql="

        SELECT *

        FROM audit_checklist_results

        WHERE checklist_id=?

        LIMIT 1

        ";

        $stmt=$this->db->prepare($sql);

        $stmt->bind_param("i",$checklistId);

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();

    }

        /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    public function getSummary(int $assignmentId):array
    {

        $sql="

        SELECT

        COUNT(*) total,

        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) selesai,

        SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) draft

        FROM audit_checklists

        WHERE assignment_id=?

        ";

        $stmt=$this->db->prepare($sql);

        $stmt->bind_param("i",$assignmentId);

        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();

    }

    }

