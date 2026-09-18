<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Institution Repository
|--------------------------------------------------------------------------
*/

class InstitutionRepository extends BaseRepository
{
    /**
     * Nama tabel
     */
    protected string $table = 'institution_profile';

    /*
    |--------------------------------------------------------------------------
    | Get Institution Profile
    |--------------------------------------------------------------------------
    */

    public function getProfile(): ?array
    {

        $stmt = $this->prepare(

            "

                SELECT *

                FROM {$this->table}

                ORDER BY id ASC

                LIMIT 1

            "

        );

        $this->execute($stmt);

        return $this->fetchOne($stmt);

    }

    /*
    |--------------------------------------------------------------------------
    | Get By ID
    |--------------------------------------------------------------------------
    */

    public function getById(
        int $id
    ): ?array
    {

        $stmt = $this->prepare(

            "

                SELECT *

                FROM {$this->table}

                WHERE id = ?

                LIMIT 1

            "

        );

        $stmt->bind_param(

            "i",

            $id

        );

        $this->execute($stmt);

        return $this->fetchOne($stmt);

    }
/*
|--------------------------------------------------------------------------
| Update Institution Profile
|--------------------------------------------------------------------------
*/

public function update(
    int $id,
    array $data
): bool
{

    $sql = "

        UPDATE {$this->table}

        SET
            foundation_name           = ?,
            logo                      = ?,
            
            institution_name         = ?,
            institution_short_name   = ?,
            institution_type         = ?,
            institution_status       = ?,

            accreditation_status     = ?,
            accreditation_number     = ?,
            accreditation_agency     = ?,
            accreditation_expired    = ?,

            address                  = ?,
            city                     = ?,
            province                 = ?,
            postal_code              = ?,

            phone                    = ?,
            email                    = ?,
            website                  = ?,

            leader_title             = ?,
            leader_name              = ?,

            quality_office_name      = ?,
            quality_office_head      = ?,

            vision                   = ?,
            mission                  = ?,

            updated_at               = NOW()

        WHERE id = ?

    ";
    $stmt = $this->prepare($sql);

$types = str_repeat("s", 23) . "i";

$stmt->bind_param(

    $types,

    $data['foundation_name'],
    $data['logo'],

    $data['institution_name'],
    $data['institution_short_name'],
    $data['institution_type'],
    $data['institution_status'],

    $data['accreditation_status'],
    $data['accreditation_number'],
    $data['accreditation_agency'],
    $data['accreditation_expired'],

    $data['address'],
    $data['city'],
    $data['province'],
    $data['postal_code'],

    $data['phone'],
    $data['email'],
    $data['website'],

    $data['leader_title'],
    $data['leader_name'],

    $data['quality_office_name'],
    $data['quality_office_head'],

    $data['vision'],
    $data['mission'],

    $id

);

    $this->execute($stmt);

    return $this->affectedRows() >= 0;

}

/*
|--------------------------------------------------------------------------
| Check Exists
|--------------------------------------------------------------------------
*/

public function exists(): bool
{

    $stmt = $this->prepare(

        "

            SELECT COUNT(*) AS total

            FROM {$this->table}

        "

    );

    $this->execute($stmt);

    $row = $this->fetchOne($stmt);

    return ((int)$row['total']) > 0;

}

/*
|--------------------------------------------------------------------------
| Create Default Profile
|--------------------------------------------------------------------------
*/

public function createDefault(): bool
{

    $stmt = $this->prepare(

        "

            INSERT INTO {$this->table}

            (

                institution_name

            )

            VALUES

            (

                'Nama Institusi'

            )

        "

    );

    $this->execute($stmt);

    return true;

}

}