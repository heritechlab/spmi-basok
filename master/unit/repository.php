<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Master Unit Repository
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../core/BaseRepository.php';

class UnitRepository extends BaseRepository
{

    protected string $table = 'units';

    public function __construct(mysqli $db)
    {
        parent::__construct($db);
    }

    /*
    |--------------------------------------------------------------------------
    | Base Select
    |--------------------------------------------------------------------------
    */

    private function baseSelect(): string
    {
        return "

            SELECT

                u.*,

                p.name AS parent_name,

                acc.id AS account_user_id,

                acc.username AS account_username

            FROM {$this->table} u

            LEFT JOIN {$this->table} p

                ON p.id = u.parent_id

            LEFT JOIN users acc

                ON acc.unit_id = u.id AND acc.role_id = 4

        ";
    }
        /*
    |--------------------------------------------------------------------------
    | Get All
    |--------------------------------------------------------------------------
    */

        public function getAll(): array
    {
        $sql = $this->baseSelect() . "

            WHERE u.status = 1

            ORDER BY

                u.sort_order ASC,

                u.name ASC

        ";

        $stmt = $this->prepare($sql);

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
        /*
    |--------------------------------------------------------------------------
    | Get By ID
    |--------------------------------------------------------------------------
    */

    public function getById(int $id): ?array
    {
        $sql = $this->baseSelect() . "

            WHERE

                u.id = ?

            LIMIT 1

        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "i",
            $id
        );

        $this->execute($stmt);

        return $this->fetchOne($stmt);
    }
        /*
    |--------------------------------------------------------------------------
    | Parent Options
    |--------------------------------------------------------------------------
    */

    public function getParentOptions(): array
    {
        $sql = "

            SELECT

                id,

                code,

                name,

                type

            FROM {$this->table}

            WHERE

                status = 1

            ORDER BY

                sort_order ASC,

                name ASC

        ";

        $stmt = $this->prepare($sql);

        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }
    /*
|--------------------------------------------------------------------------
| Exists Code
|--------------------------------------------------------------------------
*/

public function existsCode(string $code, ?int $ignoreId = null): bool
{
    $sql = "

        SELECT
            id

        FROM {$this->table}

        WHERE
            code = ?
            AND status = 1

    ";

    if ($ignoreId !== null) {

        $sql .= " AND id <> ?";

    }

    $sql .= " LIMIT 1";

    $stmt = $this->prepare($sql);

    if ($ignoreId !== null) {

        $stmt->bind_param(
            "si",
            $code,
            $ignoreId
        );

    } else {

        $stmt->bind_param(
            "s",
            $code
        );

    }

    $this->execute($stmt);

    return $this->fetchOne($stmt) !== null;
}
/*
|--------------------------------------------------------------------------
| Exists Name
|--------------------------------------------------------------------------
*/

public function existsName(string $name, ?int $ignoreId = null): bool
{
    $sql = "

        SELECT
            id

        FROM {$this->table}

        WHERE
            name = ?
            AND status = 1

    ";

    if ($ignoreId !== null) {

        $sql .= " AND id <> ?";

    }

    $sql .= " LIMIT 1";

    $stmt = $this->prepare($sql);

    if ($ignoreId !== null) {

        $stmt->bind_param(
            "si",
            $name,
            $ignoreId
        );

    } else {

        $stmt->bind_param(
            "s",
            $name
        );

    }

    $this->execute($stmt);

    return $this->fetchOne($stmt) !== null;
}
/*
|--------------------------------------------------------------------------
| Count
|--------------------------------------------------------------------------
*/

public function count(): int
{
    $sql = "

        SELECT COUNT(*) AS total

        FROM {$this->table}

        WHERE status = 1

    ";

    $stmt = $this->prepare($sql);

    $this->execute($stmt);

    $row = $this->fetchOne($stmt);

    return (int)$row['total'];
}

/*
|--------------------------------------------------------------------------
| Count Filtered
|--------------------------------------------------------------------------
*/

public function countFiltered(

    string $keyword = '',

    ?string $type = null,

    ?int $status = null

): int
{

    $sql = "

        SELECT COUNT(*) AS total

        FROM {$this->table} u

        WHERE u.status = 1

    ";

    $params = [];
    $types  = "";

    if ($keyword !== "") {

        $sql .= "

            AND (

                u.code LIKE ?

                OR u.name LIKE ?

                OR u.short_name LIKE ?

            )

        ";

        $keyword = "%{$keyword}%";

        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;

        $types .= "sss";

    }

    if ($type !== null && $type !== "") {

        $sql .= "

            AND u.type = ?

        ";

        $params[] = $type;

        $types .= "s";

    }

    if ($status === 0) {

    $sql = str_replace(
        "u.status = 1",
        "u.status = 0",
        $sql
    );

}

    $stmt = $this->prepare($sql);

    if (!empty($params)) {

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
| Create
|--------------------------------------------------------------------------
*/

public function create(array $data): int
{
    $sql = "

        INSERT INTO {$this->table}

        (

            code,

            name,

            short_name,

            type,

            parent_id,

            head_name,

            email,

            phone,

            description,

            is_auditable,

            status,

            sort_order,

            created_at

        )

        VALUES

        (

            ?,?,?,?,?,?,?,?,?,?,?,?,

            NOW()

        )

    ";

    $stmt = $this->prepare($sql);

    $stmt->bind_param(

        "ssssissssiis",

        $data['code'],
        $data['name'],
        $data['short_name'],
        $data['type'],
        $data['parent_id'],
        $data['head_name'],
        $data['email'],
        $data['phone'],
        $data['description'],
        $data['is_auditable'],
        $data['status'],
        $data['sort_order']

    );

    $this->execute($stmt);

    return $this->insertId();
}
/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

public function update(int $id, array $data): bool
{
    $sql = "

        UPDATE {$this->table}

        SET

            code=?,

            name=?,

            short_name=?,

            type=?,

            parent_id=?,

            head_name=?,

            email=?,

            phone=?,

            description=?,

            is_auditable=?,

            status=?,

            sort_order=?,

            updated_at=NOW()

        WHERE id=?

    ";

    $stmt = $this->prepare($sql);

    $stmt->bind_param(

        "ssssissssiiii",

        $data['code'],
        $data['name'],
        $data['short_name'],
        $data['type'],
        $data['parent_id'],
        $data['head_name'],
        $data['email'],
        $data['phone'],
        $data['description'],
        $data['is_auditable'],
        $data['status'],
        $data['sort_order'],
        $id

    );

    $this->execute($stmt);

    return true;
}
/*
|--------------------------------------------------------------------------
| Delete (Soft Delete)
|--------------------------------------------------------------------------
*/

public function delete(int $id): bool
{
    $sql = "

        UPDATE {$this->table}

        SET

            status=0,

            updated_at=NOW()

        WHERE id=?

    ";

    $stmt = $this->prepare($sql);

    $stmt->bind_param(

        "i",

        $id

    );

    $this->execute($stmt);

    return true;
}
/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

public function search(

    string $keyword = '',

    ?string $type = null,

    ?int $status = null

): array
{

    $sql = $this->baseSelect() . "

    WHERE u.status = 1

";

    $params = [];

    $types  = "";

    if ($keyword !== "") {

        $sql .= "

            AND (

                u.code LIKE ?

                OR u.name LIKE ?

                OR u.short_name LIKE ?

            )

        ";

        $keyword = "%{$keyword}%";

        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;

        $types .= "sss";
    }

    if ($type !== null && $type !== "") {

        $sql .= "

            AND u.type=?

        ";

        $params[] = $type;

        $types .= "s";

    }

    if ($status !== null) {

    $sql = str_replace(
        "WHERE u.status = 1",
        "WHERE 1=1",
        $sql
    );

    $sql .= "

        AND u.status = ?

    ";

        $params[] = $status;

        $types .= "i";

    }

    $sql .= "

        ORDER BY

            u.sort_order,

            u.name

    ";

    $stmt = $this->prepare($sql);

    if (!empty($params)) {

        $stmt->bind_param(

            $types,

            ...$params

        );

    }

    $this->execute($stmt);

    return $this->fetchAll($stmt);

}
/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

public function paginate(

    string $keyword = '',

    ?string $type = null,

    ?int $status = null,

    int $page = 1,

    int $limit = 10

): array
{

    $offset = ($page - 1) * $limit;

    $sql = $this->baseSelect() . "

        WHERE u.status = 1

    ";

    $params = [];

    $types = "";

    if ($keyword !== "") {

        $sql .= "

            AND (

                u.code LIKE ?

                OR u.name LIKE ?

                OR u.short_name LIKE ?

            )

        ";

        $keyword = "%{$keyword}%";

        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;

        $types .= "sss";

    }

    if ($type !== null && $type !== "") {

        $sql .= "

            AND u.type=?

        ";

        $params[] = $type;

        $types .= "s";

    }

    if ($status !== null) {

        $sql .= "

            AND u.status=?

        ";

        $params[] = $status;

        $types .= "i";

    }

    $sql .= "

        ORDER BY

            u.sort_order,

            u.name

        LIMIT ?,?

    ";

    $params[] = $offset;
    $params[] = $limit;

    $types .= "ii";

    $stmt = $this->prepare($sql);

    $stmt->bind_param(

        $types,

        ...$params

    );

    $this->execute($stmt);

    return $this->fetchAll($stmt);

}
/*
|--------------------------------------------------------------------------
| Has Children
|--------------------------------------------------------------------------
*/

public function hasChildren(int $id): bool
{
    $sql="

        SELECT id

        FROM {$this->table}

        WHERE parent_id=?

        LIMIT 1

    ";

    $stmt=$this->prepare($sql);

    $stmt->bind_param(

        "i",

        $id

    );

    $this->execute($stmt);

    return $this->fetchOne($stmt)!==null;
}

}