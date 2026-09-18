<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/../repository.php';

class AccManageService extends BaseService
{
    private AccRepository $accRepository;

    public function __construct(AccManageRepository $repository, AccRepository $accRepository)
    {
        parent::__construct($repository);
        $this->accRepository = $accRepository;
    }

    protected function repository(): AccManageRepository
    {
        /** @var AccManageRepository */
        return parent::repository();
    }

    public function getAllTables(): array
    {
        return $this->success($this->accRepository->getAllTables());
    }

    public function getTableColumns(int $tableId): array
    {
        return $this->success($this->accRepository->getTableColumns($tableId));
    }

    public function getTableDetail(string $code): array
    {
        $table = $this->accRepository->getTableByCode($code);

        if (!$table) {
            return $this->error('Tabel tidak ditemukan.');
        }

        $table['columns'] = $this->accRepository->getTableColumns((int) $table['id']);

        return $this->success($table);
    }

    public function saveTable(int $id, string $code, string $title, string $description): array
    {
        try {

            $code = trim($code);
            $title = trim($title);

            if ($title === '') {
                throw new InvalidArgumentException('Judul Tabel wajib diisi.');
            }

            if ($id > 0) {
                $this->repository()->updateTable($id, $title, $description);
                return $this->success(['id' => $id], 'Tabel berhasil diperbarui.');
            }

            if ($code === '') {
                throw new InvalidArgumentException('Kode Tabel wajib diisi.');
            }

            if ($this->repository()->codeExists($code)) {
                throw new InvalidArgumentException('Kode Tabel sudah digunakan.');
            }

            $newId = $this->repository()->createTable($code, $title, $description);

            return $this->success(['id' => $newId], 'Tabel berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteTable(int $id): array
    {
        $this->repository()->deleteTable($id);

        return $this->success(null, 'Tabel berhasil dihapus.');
    }

public function saveColumn(int $id, int $tableId, string $groupLabel, string $label, string $dataType, string $totalMode): array
    {
        try {

            $label = trim($label);
            $groupLabel = trim($groupLabel);

            if ($label === '') {
                throw new InvalidArgumentException('Nama Kolom wajib diisi.');
            }

            if (!in_array($dataType, ['integer', 'decimal', 'text', 'checkbox'], true)) {
                $dataType = 'integer';
            }

            if (!in_array($totalMode, ['sum', 'average', 'min', 'max', 'none'], true)) {
                $totalMode = 'sum';
            }

            $groupLabelValue = $groupLabel !== '' ? $groupLabel : null;

            if ($id > 0) {
                $this->repository()->updateColumn($id, $groupLabelValue, $label, $dataType, $totalMode);
                return $this->success(['id' => $id], 'Kolom berhasil diperbarui.');
            }

            $newId = $this->repository()->createColumn($tableId, $groupLabelValue, $label, $dataType, $totalMode);

            return $this->success(['id' => $newId], 'Kolom berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteColumn(int $id): array
    {
        $this->repository()->deleteColumn($id);

        return $this->success(null, 'Kolom berhasil dihapus.');
    }

    public function updateTableLabels(int $tableId, array $input): array
    {
        try {

            $labelTs2 = trim($input['label_ts2'] ?? '') ?: null;
            $labelTs1 = trim($input['label_ts1'] ?? '') ?: null;
            $labelTs = trim($input['label_ts'] ?? '') ?: null;

            $this->accRepository->updateTableLabels($tableId, $labelTs2, $labelTs1, $labelTs);

            return $this->success(null, 'Label Tahun Akademik berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
    public function getCriteria(): array
    {
        return $this->success($this->accRepository->getCriteria());
    }

    public function getAcademicYears(): array
    {
        return $this->success($this->accRepository->getAcademicYears());
    }

    public function saveAcademicYear(string $label): array
    {
        try {

            $label = trim($label);

            if ($label === '') {
                throw new InvalidArgumentException('Label Tahun Akademik wajib diisi.');
            }

            if ($this->repository()->academicYearExists($label)) {
                throw new InvalidArgumentException('Tahun Akademik ini sudah ada.');
            }

            $newId = $this->repository()->createAcademicYear($label);

            return $this->success(['id' => $newId], 'Tahun Akademik berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteAcademicYear(int $id): array
    {
        $this->repository()->deleteAcademicYear($id);

        return $this->success(null, 'Tahun Akademik berhasil dihapus.');
    }

    public function getDocumentsByCriteria(int $criteriaId): array
    {
        return $this->success($this->repository()->getDocumentsByCriteria($criteriaId));
    }

public function saveDocument(int $id, int $criteriaId, string $documentName, array $unitIds): array
    {
        try {

            $documentName = trim($documentName);

            if ($documentName === '') {
                throw new InvalidArgumentException('Nama Dokumen wajib diisi.');
            }

            if ($id > 0) {
                $this->repository()->updateDocumentName($id, $documentName);
                $this->accRepository->saveDocumentUnits($id, $unitIds);
                return $this->success(['id' => $id], 'Dokumen berhasil diperbarui.');
            }

            $newId = $this->repository()->createDocument($criteriaId, $documentName);
            $this->accRepository->saveDocumentUnits($newId, $unitIds);

            return $this->success(['id' => $newId], 'Dokumen berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getDocumentUnitIds(int $documentId): array
    {
        return $this->success($this->accRepository->getDocumentUnitIds($documentId));
    }

    public function getTableUnitIds(int $tableId): array
    {
        return $this->success($this->accRepository->getTableUnitIds($tableId));
    }

    public function saveTableUnits(int $tableId, array $unitIds): array
    {
        $this->accRepository->saveTableUnits($tableId, $unitIds);

        return $this->success(null, 'Unit Kerja pemilik Tabel berhasil disimpan.');
    }

    public function deleteDocument(int $id): array
    {
        $this->repository()->deleteDocument($id);

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }

    public function updateTableCriteria(int $tableId, int $criteriaId): array
    {
        $this->repository()->updateTableCriteria($tableId, $criteriaId > 0 ? $criteriaId : null);

        return $this->success(null, 'Kriteria untuk Tabel ini berhasil disimpan.');
    }
}