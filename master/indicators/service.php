<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SIQUA Enterprise
|--------------------------------------------------------------------------
| Module : Master Indikator
| File   : service.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class IndicatorService extends BaseService
{
    public function __construct(
        IndicatorRepository $repository
    ) {
        parent::__construct($repository);
    }

    /*
    |--------------------------------------------------------------------------
    | get PTP STATUS PENINGKATAN
    |--------------------------------------------------------------------------
    */
    public function getPtpStatusMap(array $indicatorIds): array
    {
        return $this->repository()->getPtpStatusMap($indicatorIds);
    }

    /*
    |--------------------------------------------------------------------------
    | Repository
    |--------------------------------------------------------------------------
    */

    protected function repository(): IndicatorRepository
    {
        /** @var IndicatorRepository */
        return parent::repository();
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard Statistics
    |--------------------------------------------------------------------------
    */

    public function statistics(): array
    {
        return $this->success(
            $this->repository()->getStatistics()
        );
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

        return $this->success(

            $this->repository()->getAll(

                $search,

                $standardId,

                $indicatorType,

                $limit,

                $offset,

                $unitId

            )

        );

    }

        /*
    |--------------------------------------------------------------------------
    | Count
    |--------------------------------------------------------------------------
    */

public function count(
        string $search = '',
        int $standardId = 0,
        string $indicatorType = '',
        int $unitId = 0
    ): array
    {

        return $this->success(

            $this->repository()->count(

                $search,

                $standardId,

                $indicatorType,

                $unitId

            )

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Get By ID
    |--------------------------------------------------------------------------
    */

    public function getById(
        int $id
    ): array {

        $data = $this->repository()->getById($id);

        if (!$data) {

            return $this->error(
                'Data indikator tidak ditemukan.'
            );

        }

        return $this->success($data);

    }

    /*
    |--------------------------------------------------------------------------
    | Standards
    |--------------------------------------------------------------------------
    */

    public function standards(): array
    {
        return $this->success(

            $this->repository()->getStandards()

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(
        int $id,
        array $data
    ): array {

        try {

$this->required($data, [

                'standard_id',
                'item_code',
                'statement_id',
                'indicator',
                'target'

            ]);

            $statementId = (int) ($data['statement_id'] ?? 0);
            $data['statement'] = $this->repository()->getStatementText($statementId) ?? '';
            $data['statement_id'] = $statementId > 0 ? $statementId : null;

            if (

                $this->repository()->existsCode(

                    $data['item_code'],

                    $id

                )

            ) {

                return $this->error(

                    'Kode indikator sudah digunakan.'

                );

            }

        $this->repository()->update(
                $id,
                $data
            );

            $unitIds = $data['unit_ids'] ?? [];
            if (!is_array($unitIds)) {
                $unitIds = [];
            }
            $this->repository()->saveIndicatorUnits($id, $unitIds);

            return $this->success(

                null,

                'Data indikator berhasil diperbarui.'

            );

        } catch (Throwable $e) {

            return $this->error(

                $e->getMessage()

            );

        }

    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

        public function delete(int $id): array
    {

        if($id<=0){

            return $this->error(

                "ID indikator tidak valid."

            );

        }

        $this->repository()->delete($id);

        return $this->success(

            [],

            "Indikator berhasil dinonaktifkan."

        );

    }
/*
|--------------------------------------------------------------------------
| Create
|--------------------------------------------------------------------------
*/

public function create(array $input): array
{

    if (empty(trim($input['item_code'] ?? ''))) {

        return [

            'success' => false,

            'message' => 'Kode indikator wajib diisi.'

        ];

    }

    if (empty($input['standard_id'])) {

        return [

            'success' => false,

            'message' => 'Standar wajib dipilih.'

        ];

    }

    if (empty(trim($input['indicator'] ?? ''))) {

        return [

            'success' => false,

            'message' => 'Indikator wajib diisi.'

        ];

    }

    if (empty(trim($input['indicator_type'] ?? ''))) {

        return [

            'success' => false,

            'message' => 'Jenis indikator wajib dipilih.'

        ];

    }

$statementId = (int) ($input['statement_id'] ?? 0);
    $statementText = $this->repository->getStatementText($statementId) ?? '';

    $id = $this->repository->create([

        'standard_id'         => (int)$input['standard_id'],

        'indicator_type'      => trim($input['indicator_type']),

        'item_code'           => trim($input['item_code']),

        'statement'           => $statementText,

        'statement_id'        => $statementId > 0 ? $statementId : null,

        'indicator'           => trim($input['indicator']),

        'strategi'            => trim($input['strategi'] ?? ''),

        'target'              => trim($input['target'] ?? ''),

        'verification_method' => trim($input['verification_method'] ?? ''),

        'weight'              => (float)($input['weight'] ?? 0),

        'status'              => (int)($input['status'] ?? 1),

        'sort_order'          => 0

    ]);

    $unitIds = $input['unit_ids'] ?? [];
    if (!is_array($unitIds)) {
        $unitIds = [];
    }
    $this->repository->saveIndicatorUnits($id, $unitIds);

    return [

        'success' => true,

        'message' => 'Indikator berhasil disimpan.',

        'data'    => $id

    ];

}
public function getStatementsByStandard(int $standardId): array
    {
        return $this->success($this->repository()->getStatementsByStandard($standardId));
    }
}