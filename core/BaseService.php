<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseRepository.php';

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Base Service
|--------------------------------------------------------------------------
|
| Menjadi parent seluruh Service SIQUA
|
*/

abstract class BaseService
{
    protected BaseRepository $repository;

    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /*
    |--------------------------------------------------------------------------
    | Response Success
    |--------------------------------------------------------------------------
    */

    protected function success(
        mixed $data = null,
        string $message = 'Success'
    ): array {

        return [
            'success' => true,
            'message' => $message,
            'data'    => $data
        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Response Error
    |--------------------------------------------------------------------------
    */

    protected function error(
        string $message = 'Error',
        mixed $errors = null
    ): array {

        return [
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    protected function required(
        array $data,
        array $fields
    ): void {

        foreach ($fields as $field) {

            if (
                !isset($data[$field]) ||
                trim((string)$data[$field]) === ''
            ) {

                throw new InvalidArgumentException(
                    "Field '{$field}' wajib diisi."
                );

            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Helper
    |--------------------------------------------------------------------------
    */

    protected function begin(): void
    {
        $this->repository->begin();
    }

    protected function commit(): void
    {
        $this->repository->commit();
    }

    protected function rollback(): void
    {
        $this->repository->rollback();
    }

    /*
    |--------------------------------------------------------------------------
    | Repository
    |--------------------------------------------------------------------------
    */

    protected function repository(): BaseRepository
    {
        return $this->repository;
    }
}