<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class IndicatorRepository extends BaseRepository
{
    protected string $table = 'audit_indicators';

    /*
    |--------------------------------------------------------------------------
    | Dashboard Statistics
    |--------------------------------------------------------------------------
    */

 public function getStatistics(): array
    {
        return [
            'total'       => $this->count(),
            'active'      => $this->countByStatus(1),
            'inactive'    => $this->countByStatus(0),
            'standards'   => $this->countStandards(),
            'iku_wajib'   => $this->countByIndicatorType('IKU Wajib'),
            'iku_pilihan' => $this->countByIndicatorType('IKU Pilihan'),
            'iku_pt'      => $this->countByIndicatorType('IKU PT'),
            'ikt'         => $this->countByIndicatorType('IKT'),
        ];
    }

    public function countByIndicatorType(string $type): int
    {
        $stmt = $this->prepare("
            SELECT COUNT(*) total
            FROM audit_indicators
            WHERE indicator_type = ? AND status = 1
        ");

        $stmt->bind_param("s", $type);

        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

/*
|--------------------------------------------------------------------------
| Get All
|--------------------------------------------------------------------------
*/

public function getAll(
    string $search = '',
    int $standardId = 0,
    string $indicatorType = '',
    int $limit = 10,
    int $offset = 0,
    int $unitId = 0
): array
{

$sql = "

        SELECT

            ai.id,

            ai.standard_id,

            s.name AS standard_name,

            ai.indicator_type,

            ai.item_code,

            ai.statement,

            ai.indicator,

            ai.strategi,

            ai.target,

            ai.status,

            (SELECT r.level_risiko FROM risk_register r
                WHERE r.sumber_jenis = 'indikator' AND r.sumber_id = ai.id AND r.level_risiko IS NOT NULL
                ORDER BY FIELD(r.level_risiko,'Ekstrem','Tinggi','Sedang','Rendah') ASC LIMIT 1) AS risk_level_tertinggi,

            (SELECT COUNT(*) FROM risk_register r WHERE r.sumber_jenis = 'indikator' AND r.sumber_id = ai.id) AS jumlah_risiko

        FROM audit_indicators ai

        LEFT JOIN standards s
            ON s.id = ai.standard_id

    ";

    $where  = [];
    $types  = '';
    $params = [];

    /*
|--------------------------------------------------------------------------
| Default hanya tampilkan jenis indikator
|--------------------------------------------------------------------------
*/

$where[] = "ai.status = 1";

if ($unitId > 0) {

    $where[] = "
        (
            NOT EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id)
            OR EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id AND aiu.unit_id = ?)
        )
    ";

    $types .= "i";
    $params[] = $unitId;

}

if ($indicatorType !== '') {

    $where[] = "ai.indicator_type = ?";

    $types .= "s";

    $params[] = $indicatorType;

}

    /*
    |--------------------------------------------------------------------------
    | Filter Standar
    |--------------------------------------------------------------------------
    */

    if ($standardId > 0) {

        $where[] = "ai.standard_id = ?";

        $types .= "i";

        $params[] = $standardId;

    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $where[] = "

            (

                ai.item_code LIKE ?

                OR s.name LIKE ?

                OR ai.statement LIKE ?

                OR ai.indicator LIKE ?

                OR ai.target LIKE ?

            )

        ";

        $keyword = "%{$search}%";

        $types .= "sssss";

        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;

    }

    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    if (!empty($where)) {

        $sql .= " WHERE " . implode(" AND ", $where);

    }

    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "

        ORDER BY

            ai.sort_order,

            ai.item_code

        LIMIT ?

        OFFSET ?

    ";

    $types .= "ii";

    $params[] = $limit;

    $params[] = $offset;

    $stmt = $this->prepare($sql);

    /*
    |--------------------------------------------------------------------------
    | Bind Dynamic Parameter
    |--------------------------------------------------------------------------
    */

    $stmt->bind_param(

        $types,

        ...$params

    );

    $this->execute($stmt);

    return $this->fetchAll($stmt);

}

/*
|--------------------------------------------------------------------------
| Count Data
|--------------------------------------------------------------------------
*/
public function count(
    string $search = '',
    int $standardId = 0,
    string $indicatorType = '',
    int $unitId = 0
): int
{

    $sql = "

        SELECT

            COUNT(*) AS total

        FROM audit_indicators ai

        LEFT JOIN standards s
            ON s.id = ai.standard_id

    ";

    $where  = [];
    $types  = '';
    $params = [];

    $where[] = "ai.status = 1";

    if ($unitId > 0) {

        $where[] = "
            (
                NOT EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id)
                OR EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id AND aiu.unit_id = ?)
            )
        ";

        $types .= "i";
        $params[] = $unitId;

    }

    if ($indicatorType !== '') {

        $where[] = "ai.indicator_type = ?";

        $types .= "s";

        $params[] = $indicatorType;

    }

    /*
    |--------------------------------------------------------------------------
    | Filter Standar
    |--------------------------------------------------------------------------
    */

    if ($standardId > 0) {

        $where[] = "ai.standard_id = ?";

        $types .= "i";

        $params[] = $standardId;

    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $where[] = "

            (

                ai.item_code LIKE ?

                OR s.name LIKE ?

                OR ai.statement LIKE ?

                OR ai.indicator LIKE ?

                OR ai.target LIKE ?

            )

        ";

        $keyword = "%{$search}%";

        $types .= "sssss";

        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;

    }

    /*
    |--------------------------------------------------------------------------
    | WHERE
    |--------------------------------------------------------------------------
    */

    if (!empty($where)) {

        $sql .= " WHERE " . implode(" AND ", $where);

    }

    $stmt = $this->prepare($sql);

    /*
    |--------------------------------------------------------------------------
    | Dynamic Bind
    |--------------------------------------------------------------------------
    */

    if ($types !== '') {

        $stmt->bind_param(
            $types,
            ...$params
        );

    }

    $this->execute($stmt);

    $row = $this->fetchOne($stmt);

    return (int)($row['total'] ?? 0);

}
/*
    |--------------------------------------------------------------------------
    | Pernyataan standar
    |--------------------------------------------------------------------------
    */

public function getStatementsByStandard(int $standardId): array
    {
        $stmt = $this->prepare("
            SELECT id, statement_text
            FROM standard_statements
            WHERE standard_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->bind_param("i", $standardId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    /*
    |--------------------------------------------------------------------------
    | Count Status
    |--------------------------------------------------------------------------
    */

    public function countByStatus(int $status): int
    {

        $stmt = $this->prepare("
            SELECT COUNT(*) total

            FROM audit_indicators

            WHERE status=?
        ");

        $stmt->bind_param("i", $status);

        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);

    }

    /*
    |--------------------------------------------------------------------------
    | Count Standard
    |--------------------------------------------------------------------------
    */

    public function countStandards(): int
    {

        $stmt = $this->prepare("
            SELECT COUNT(*) total

            FROM standards

            WHERE is_active=1
        ");

        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        return (int)$row['total'];

    }

    /*
    |--------------------------------------------------------------------------
    | Get By ID
    |--------------------------------------------------------------------------
    */

    public function getById(int $id): ?array
    {

        $stmt = $this->prepare("
            SELECT *

            FROM audit_indicators

            WHERE id=?
        ");

        $stmt->bind_param("i",$id);

        $this->execute($stmt);

        return $this->fetchOne($stmt);

    }
    /*
|--------------------------------------------------------------------------
| Exists Item Code
|--------------------------------------------------------------------------
*/

public function existsCode(
    string $itemCode,
    int $ignoreId = 0
): bool
{

    $sql = "

        SELECT COUNT(*) AS total

        FROM audit_indicators

        WHERE item_code = ?

        AND status = 1

    ";

    if ($ignoreId > 0) {
        $sql .= " AND id <> ?";
    }

    $stmt = $this->prepare($sql);

    if ($ignoreId > 0) {

        $stmt->bind_param(
            "si",
            $itemCode,
            $ignoreId
        );

    } else {

        $stmt->bind_param(
            "s",
            $itemCode
        );

    }

    $this->execute($stmt);

    $row = $this->fetchOne($stmt);

    return ((int)$row['total']) > 0;

}
    /*
|--------------------------------------------------------------------------
| Get Standards
|--------------------------------------------------------------------------
*/

public function getStandards(): array
        {
            $sql = "

                SELECT

                    id,

                    code,

                    name

                FROM standards

                WHERE status = 1

                ORDER BY

                    sort_order ASC,

                    code ASC

            ";

            $stmt = $this->prepare($sql);

            $this->execute($stmt);

            return $this->fetchAll($stmt);
        }
/*
|--------------------------------------------------------------------------
| Create Indicator
|--------------------------------------------------------------------------
*/

public function create(array $data): int
{

    $sql = "

        INSERT INTO audit_indicators (

            standard_id,
            indicator_type,
            item_code,
            statement,
            statement_id,
            indicator,
            strategi,
            target,
            verification_method,
            weight,
            status,
            sort_order

        )

        VALUES (

            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?

        )

    ";

    $stmt = $this->prepare($sql);

    $stmt->bind_param(

        "isssissssdii",

        $data['standard_id'],
        $data['indicator_type'],
        $data['item_code'],
        $data['statement'],
        $data['statement_id'],
        $data['indicator'],
        $data['strategi'],
        $data['target'],
        $data['verification_method'],
        $data['weight'],
        $data['status'],
        $data['sort_order']

    );

    $this->execute($stmt);

    return $this->insertId();

}

/*
|--------------------------------------------------------------------------
| Unit Kerja Indikator (banyak-ke-banyak)
|--------------------------------------------------------------------------
*/

public function getIndicatorUnitIds(int $indicatorId): array
{
    $stmt = $this->prepare("SELECT unit_id FROM audit_indicator_units WHERE indicator_id = ?");
    $stmt->bind_param("i", $indicatorId);
    $this->execute($stmt);

    return array_column($this->fetchAll($stmt), 'unit_id');
}

public function saveIndicatorUnits(int $indicatorId, array $unitIds): void
{
    $del = $this->prepare("DELETE FROM audit_indicator_units WHERE indicator_id = ?");
    $del->bind_param("i", $indicatorId);
    $this->execute($del);

    if (empty($unitIds)) {
        return;
    }

    $ins = $this->prepare("INSERT INTO audit_indicator_units (indicator_id, unit_id) VALUES (?, ?)");

    foreach ($unitIds as $unitId) {
        $unitId = (int) $unitId;
        $ins->bind_param("ii", $indicatorId, $unitId);
        $this->execute($ins);
    }
}
/*
|--------------------------------------------------------------------------
| Update Indicator
|--------------------------------------------------------------------------
*/

public function update(
    int $id,
    array $data
): bool
{

    $sql = "

        UPDATE audit_indicators

        SET

            standard_id = ?,

            indicator_type=?,

            item_code = ?,

            statement = ?,

            statement_id = ?,

            indicator = ?,

            strategi = ?,

            target = ?,

            verification_method = ?,

            weight = ?,

            status = ?,

            updated_at = NOW()

        WHERE id = ?

    ";

            $stmt = $this->prepare($sql);

            $stmt->bind_param(

            "isssissssdii",

            $data['standard_id'],
            $data['indicator_type'],
            $data['item_code'],
            $data['statement'],
            $data['statement_id'],
            $data['indicator'],
            $data['strategi'],
            $data['target'],
            $data['verification_method'],
            $data['weight'],
            $data['status'],
            $id

        );

    $this->execute($stmt);

    return $this->affectedRows() >= 0;  

}
/*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

        public function delete(int $id): bool
    {

        $stmt = $this->prepare("

            UPDATE audit_indicators

            SET

                status=0,

                updated_at=NOW()

            WHERE id=?

        ");

        $stmt->bind_param(

            "i",

            $id

        );

        $this->execute($stmt);

        return true;

    }
    public function getPtpStatusMap(array $indicatorIds): array
    {
        if (empty($indicatorIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($indicatorIds), '?'));
        $types = str_repeat('i', count($indicatorIds));

        $sql = "
            SELECT audit_indicator_id, MAX(applied_at) AS last_applied
            FROM ptp_items
            WHERE status = 'Ditingkatkan' AND audit_indicator_id IN ($placeholders)
            GROUP BY audit_indicator_id
        ";

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$indicatorIds);
        $this->execute($stmt);

        $rows = $this->fetchAll($stmt);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['audit_indicator_id']] = $row['last_applied'];
        }

        return $map;
    }

    public function getStatementText(int $statementId): ?string
    {
        if ($statementId <= 0) {
            return null;
        }

        $stmt = $this->prepare("SELECT statement_text FROM standard_statements WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $statementId);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);

        return $row['statement_text'] ?? null;
    }

}