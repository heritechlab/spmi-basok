<?php

declare(strict_types=1);

require_once __DIR__ . '/repository.php';

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Institution Service
|--------------------------------------------------------------------------
*/

class InstitutionService
{

    private InstitutionRepository $repository;

    public function __construct(
        InstitutionRepository $repository
    )
    {

        $this->repository = $repository;

    }

    /*
    |--------------------------------------------------------------------------
    | Get Profile
    |--------------------------------------------------------------------------
    */

    public function getProfile(): array
    {

        if (!$this->repository->exists()) {

            $this->repository->createDefault();

        }

        return $this->repository->getProfile();

    }

    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    public function update(array $data, array $files = []): array
    {

        /*
        |--------------------------------------------------------------------------
        | Required Validation
        |--------------------------------------------------------------------------
        */

        $required = [

            'institution_name',

            'institution_short_name',

            'institution_type',

            'institution_status',

            'address',

            'city',

            'province',

            'leader_name',

            'quality_office_name'

        ];

        foreach ($required as $field) {

            if (

                !isset($data[$field]) ||

                trim((string)$data[$field]) === ''

            ) {

                return [

                    'success' => false,

                    'message' => 'Field wajib belum lengkap.'

                ];

            }

        }

        /*
        |--------------------------------------------------------------------------
        | Email Validation
        |--------------------------------------------------------------------------
        */

        if (

            !empty($data['email']) &&

            !filter_var($data['email'], FILTER_VALIDATE_EMAIL)

        ) {

            return [

                'success' => false,

                'message' => 'Format email tidak valid.'

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Website
        |--------------------------------------------------------------------------
        */

        if (!isset($data['website'])) {

            $data['website'] = '';

        }

        /*
        |--------------------------------------------------------------------------
        | Accreditation
        |--------------------------------------------------------------------------
        */

        $data['accreditation_status']   = $data['accreditation_status']   ?? '';

        $data['accreditation_number']   = $data['accreditation_number']   ?? '';

        $data['accreditation_agency']   = $data['accreditation_agency']   ?? '';

        $data['accreditation_expired']  = $data['accreditation_expired']  ?? null;

        /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */

        $data['postal_code'] = $data['postal_code'] ?? '';

        $data['phone'] = $data['phone'] ?? '';

        $data['email'] = $data['email'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Leader
        |--------------------------------------------------------------------------
        */

        $data['leader_title'] = $data['leader_title'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Quality Office
        |--------------------------------------------------------------------------
        */

        $data['quality_office_head'] =

            $data['quality_office_head'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Vision Mission
        |--------------------------------------------------------------------------
        */

$data['vision'] = $data['vision'] ?? '';

        $data['mission'] = $data['mission'] ?? '';

        $data['foundation_name'] = $data['foundation_name'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Logo Upload
        |--------------------------------------------------------------------------
        */

        $existing = $this->repository->getProfile();

        $data['logo'] = $existing['logo'] ?? '';

        if (!empty($files['logo']['name'])) {

            require_once __DIR__ . '/../../core/UploadHelper.php';

            $uploader = new UploadHelper(
                dirname(__DIR__, 2) . '/uploads/institution',
                ['png', 'jpg', 'jpeg'],
                ['image/png', 'image/jpeg']
            );

            $uploaded = $uploader->upload($files['logo']);

            $data['logo'] = $uploaded['document_file'];
        }

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $this->repository->update(

            1,

            $data

        );

        return [

            'success' => true,

            'message' => 'Profil institusi berhasil diperbarui.'

        ];

    }

}