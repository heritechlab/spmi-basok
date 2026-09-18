<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Master Unit Service
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/repository.php';

class UnitService
{

    private UnitRepository $repository;

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }

    /*
|--------------------------------------------------------------------------
| Get All
|--------------------------------------------------------------------------
*/

public function getAll(): array
{
    return $this->repository->getAll();
}

/*
|--------------------------------------------------------------------------
| Get By ID
|--------------------------------------------------------------------------
*/

public function getById(int $id): ?array
{
    return $this->repository->getById($id);
}
/*
|--------------------------------------------------------------------------
| Parent Options
|--------------------------------------------------------------------------
*/

public function getParentOptions(): array
{
    return $this->repository->getParentOptions();
}

/*
|--------------------------------------------------------------------------
| Validate Create
|--------------------------------------------------------------------------
*/

private function validateCreate(array $data): void
{
    if (trim($data['code']) === '') {
        throw new InvalidArgumentException(
            'Kode Unit wajib diisi.'
        );
    }

    if (trim($data['name']) === '') {
        throw new InvalidArgumentException(
            'Nama Unit wajib diisi.'
        );
    }

    if ($this->repository->existsCode($data['code'])) {
        throw new RuntimeException(
            'Kode Unit sudah digunakan.'
        );
    }

    if ($this->repository->existsName($data['name'])) {
        throw new RuntimeException(
            'Nama Unit sudah digunakan.'
        );
    }
}
/*
|--------------------------------------------------------------------------
| Validate Update
|--------------------------------------------------------------------------
*/

private function validateUpdate(
    int $id,
    array $data
): void
{
    if (!$this->repository->getById($id)) {
        throw new RuntimeException(
            'Data Unit tidak ditemukan.'
        );
    }

    if ($this->repository->existsCode(
        $data['code'],
        $id
    )) {
        throw new RuntimeException(
            'Kode Unit sudah digunakan.'
        );
    }

    if ($this->repository->existsName(
        $data['name'],
        $id
    )) {
        throw new RuntimeException(
            'Nama Unit sudah digunakan.'
        );
    }

    if (
        !empty($data['parent_id']) &&
        (int)$data['parent_id'] === $id
    ) {
        throw new RuntimeException(
            'Parent Unit tidak boleh dirinya sendiri.'
        );
    }
}
/*
|--------------------------------------------------------------------------
| Validate Delete
|--------------------------------------------------------------------------
*/

private function validateDelete(int $id): void
{
    if (!$this->repository->getById($id)) {

        throw new RuntimeException(
            "Data Unit tidak ditemukan."
        );

    }

    if ($this->repository->hasChildren($id)) {

        throw new RuntimeException(
            "Unit masih memiliki sub unit."
        );

    }
}
/*
|--------------------------------------------------------------------------
| Save
|--------------------------------------------------------------------------
*/

public function save(array $data): int
{
    // Normalisasi parent_id
    if (
        !isset($data['parent_id']) ||
        trim((string)$data['parent_id']) === ''
    ) {
        $data['parent_id'] = null;
    } else {
        $data['parent_id'] = (int)$data['parent_id'];
    }

    $this->validateCreate($data);

    return $this->repository->create($data);
}
/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

public function update(
    int $id,
    array $data
): bool
{
    if (
        !isset($data['parent_id']) ||
        trim((string)$data['parent_id']) === ''
    ) {
        $data['parent_id'] = null;
    } else {
        $data['parent_id'] = (int)$data['parent_id'];
    }

    $this->validateUpdate($id, $data);

    return $this->repository->update($id, $data);
}
/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

public function delete(
    int $id
): bool
{
    $this->validateDelete($id);

    return $this->repository->delete($id);
}
/*
|--------------------------------------------------------------------------
| Count
|--------------------------------------------------------------------------
*/

public function count(): int
{
    return $this->repository->count();
}
/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

public function search(

    string $keyword='',

    ?string $type=null,

    ?int $status=null

): array
{
    return $this->repository->search(

        $keyword,

        $type,

        $status

    );
}
/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

public function paginate(

    string $keyword='',

    ?string $type=null,

    ?int $status=null,

    int $page=1,

    int $limit=10

): array
{
    return $this->repository->paginate(

        $keyword,

        $type,

        $status,

        $page,

        $limit

    );
}

/*
|--------------------------------------------------------------------------
| Count Filtered
|--------------------------------------------------------------------------
*/

public function countFiltered(

    string $keyword='',

    ?string $type=null,

    ?int $status=null

): int
{

    return $this->repository->countFiltered(

        $keyword,

        $type,

        $status

    );

}


}