<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class IkuService extends BaseService
{
    public function __construct(IkuRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): IkuRepository
    {
        /** @var IkuRepository */
        return parent::repository();
    }

    public function getCriteria(): array
    {
        return $this->success($this->repository()->getCriteria());
    }

    public function getProdiUnits(): array
    {
        return $this->success($this->repository()->getProdiUnits());
    }

    public function getIndicatorsWithData(string $kategori, int $tahun, string $triwulan, ?int $unitId): array
    {
        $flatIndicators = $this->repository()->getIndicatorsByKategori($kategori, $unitId ?? 0);
        $targets = $this->repository()->getTargets($tahun);
        $realizations = $this->repository()->getRealizations($tahun, $triwulan, $unitId);

        $byId = [];
        foreach ($flatIndicators as $ind) {
            $ind['target'] = $targets[$ind['id']] ?? null;
            $ind['realization'] = $realizations[$ind['id']] ?? null;
            $ind['children'] = [];
            $byId[$ind['id']] = $ind;
        }

        $tree = [];

        foreach ($byId as $id => $ind) {
            if ($ind['parent_id'] === null) {
                $tree[] = &$byId[$id];
            } else {
                if (isset($byId[$ind['parent_id']])) {
                    $byId[$ind['parent_id']]['children'][] = &$byId[$id];
                }
            }
        }

        return $this->success($tree);
    }

    public function saveRealization(array $input, array $file, int $updatedBy): array
    {
        try {

            $indicatorId = (int) ($input['indicator_id'] ?? 0);
            $tahun = (int) ($input['tahun'] ?? 0);
            $triwulan = trim($input['triwulan'] ?? '');
            $unitId = !empty($input['unit_id']) ? (int) $input['unit_id'] : null;

            if ($indicatorId <= 0 || $tahun <= 0 || !in_array($triwulan, ['TW1', 'TW2', 'TW3', 'TW4'], true)) {
                throw new InvalidArgumentException('Parameter tidak valid.');
            }

            $buktiFile = null;
            $buktiOriginalName = null;

            if (!empty($file['name'])) {

                require_once __DIR__ . '/../core/UploadHelper.php';

                $uploader = new UploadHelper(
                    dirname(__DIR__) . '/uploads/iku_bukti',
                    ['pdf'],
                    ['application/pdf']
                );

                $result = $uploader->upload($file);

                $buktiFile = $result['document_file'];
                $buktiOriginalName = $result['document_original_name'];
            }

            $data = [
                'indicator_id'        => $indicatorId,
                'unit_id'             => $unitId,
                'tahun'               => $tahun,
                'triwulan'            => $triwulan,
                'realisasi'           => trim($input['realisasi'] ?? ''),
                'analisis'            => trim($input['analisis'] ?? ''),
                'bukti_file'          => $buktiFile,
                'bukti_original_name' => $buktiOriginalName,
                'updated_by'          => $updatedBy,
            ];

            $this->repository()->saveRealization($data);

            return $this->success(null, 'Realisasi berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
    public function getAllIndicatorsFlat(): array
    {
        return $this->success($this->repository()->getAllIndicatorsFlat());
    }

    public function updateIndicator(int $id, array $input): array
    {
        try {

            $name = trim($input['name'] ?? '');

            if ($name === '') {
                throw new InvalidArgumentException('Nama Indikator wajib diisi.');
            }

            $data = [
                'code'        => trim($input['code'] ?? ''),
                'name'        => $name,
                'satuan'      => trim($input['satuan'] ?? ''),
                'direction'   => in_array($input['direction'] ?? '', ['tinggi', 'rendah'], true) ? $input['direction'] : 'tinggi',
                'kategori'    => in_array($input['kategori'] ?? '', ['wajib', 'pilihan', 'partisipatif'], true) ? $input['kategori'] : 'wajib',
                'is_selected' => !empty($input['is_selected']) ? 1 : 0,
            ];

            $this->repository()->updateIndicator($id, $data);

            return $this->success(null, 'Indikator berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function saveTarget(int $indicatorId, int $tahun, string $baseline, string $target): array
    {
        try {

            $this->repository()->upsertTarget($indicatorId, $tahun, trim($baseline), trim($target));

            return $this->success(null, 'Baseline & Target berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getIndicatorUnitIds(int $indicatorId): array
    {
        return $this->success($this->repository()->getIndicatorUnitIds($indicatorId));
    }

    public function saveIndicatorUnits(int $indicatorId, array $unitIds): array
    {
        $this->repository()->saveIndicatorUnits($indicatorId, $unitIds);

        return $this->success(null, 'Penugasan Unit Kerja berhasil disimpan.');
    }

    public function getAssignedIndicatorCount(int $unitId, int $tahun, string $triwulan): array
    {
        return $this->success($this->repository()->getAssignedIndicatorCount($unitId, $tahun, $triwulan));
    }
}