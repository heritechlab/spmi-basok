<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Core Base Repository
|--------------------------------------------------------------------------
*/

abstract class BaseRepository
{
    protected mysqli $db;

    protected string $table = '';

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

        /*
    |--------------------------------------------------------------------------
    | Connection
    |--------------------------------------------------------------------------
    */

    protected function connection(): mysqli
    {
        return $this->db;
    }

    public function getConnection(): mysqli
    {
        return $this->db;
    }
    /*
    |--------------------------------------------------------------------------
    | Query Helper
    |--------------------------------------------------------------------------
    */

    protected function prepare(string $sql): mysqli_stmt
    {
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException(
                $this->db->error
            );
        }

        return $stmt;
    }

    protected function execute(
        mysqli_stmt $stmt
    ): bool {

        return $stmt->execute();

    }

    protected function insertId(): int
    {
        return (int)$this->db->insert_id;
    }

    protected function affectedRows(): int
    {
        return $this->db->affected_rows;
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction
    |--------------------------------------------------------------------------
    */

    public function begin(): void
    {
        $this->db->begin_transaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollback(): void
    {
        $this->db->rollback();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function fetchOne(mysqli_stmt $stmt): ?array
    {
        $result = $stmt->get_result();

        if (!$result) {
            return null;
        }

        return $result->fetch_assoc() ?: null;
    }

    protected function fetchAll(mysqli_stmt $stmt): array
    {
        $result = $stmt->get_result();

        if (!$result) {
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}