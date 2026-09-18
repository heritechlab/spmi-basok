<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AccService extends BaseService
{
    public function __construct(AccRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AccRepository
    {
        /** @var AccRepository */
        return parent::repository();
    }

    public function getAllTables(): array
    {
        return $this->success($this->repository()->getAllTables());
    }

    public function getProdiUnits(): array
    {
        return $this->success($this->repository()->getProdiUnits());
    }

    public function getAllUnits(): array
    {
        return $this->success($this->repository()->getAllUnits());
    }

    public function getTableWithData(string $code, int $unitId): array
    {
        $table = $this->repository()->getTableByCode($code);

        if (!$table) {
            return $this->error('Tabel Akreditasi tidak ditemukan.');
        }

        $tableId = (int) $table['id'];

        $columns = $this->repository()->getTableColumns($tableId, $unitId);

$rowMode = $table['row_mode'] ?? 'dynamic';

        $rows = [];

        if ($rowMode === 'fixed') {

            $rowDefs = $this->repository()->getRowDefs($tableId);
            $itemRowsByDefId = $this->repository()->getOrCreateFixedRows($tableId, $unitId, $rowDefs);

$rawData = $this->repository()->getData($tableId, $unitId);
            $dataMap = [];
            foreach ($rawData as $d) {
                $dataMap[$d['row_id']][$d['column_id']] = ['value' => $d['value'], 'text_value' => $d['text_value']];
            }

            foreach ($rowDefs as $def) {

                if ($def['row_type'] === 'header') {
                    $rows[] = ['type' => 'header', 'label' => $def['label']];
                    continue;
                }

                if ($def['row_type'] === 'item') {

                    $itemRow = $itemRowsByDefId[$def['id']] ?? null;
                    if (!$itemRow) {
                        continue;
                    }

                    $cells = [];
                    foreach ($columns as $col) {
                        $raw = $dataMap[$itemRow['id']][$col['id']] ?? null;
                        $cells[$col['id']] = $col['data_type'] === 'text' ? ($raw['text_value'] ?? null) : ($raw['value'] ?? null);
                    }

                    $rows[] = [
                        'type'      => 'item',
                        'id'        => $itemRow['id'],
                        'label'     => $itemRow['label'],
                        'group_key' => $def['group_key'],
                        'cells'     => $cells,
                    ];
                    continue;
                }

                if ($def['row_type'] === 'total') {

                    $groupKey = $def['group_key'];
                    $cells = [];

                    foreach ($columns as $col) {

                        $sum = 0;
                        $has = false;

                        foreach ($rows as $r) {
                            if (($r['type'] ?? '') === 'item' && ($r['group_key'] ?? null) === $groupKey) {
                                if ($r['cells'][$col['id']] !== null) {
                                    $sum += (float) $r['cells'][$col['id']];
                                    $has = true;
                                }
                            }
                        }

                        $cells[$col['id']] = $has ? $sum : null;
                    }

                    $rows[] = ['type' => 'total', 'label' => $def['label'], 'group_key' => $groupKey, 'cells' => $cells];
                }
            }

} elseif ($rowMode === 'grouped') {

            $headers = $this->repository()->getGroupHeaders($tableId);
            $totalDefs = $this->repository()->getGroupTotalDefs($tableId);

            $rawData = $this->repository()->getData($tableId, $unitId);
            $dataMap = [];
            foreach ($rawData as $d) {
                $dataMap[$d['row_id']][$d['column_id']] = ['value' => $d['value'], 'text_value' => $d['text_value']];
            }

            foreach ($headers as $header) {

                $rows[] = ['type' => 'header', 'label' => $header['label'], 'group_key' => $header['group_key']];

                $itemRows = $this->repository()->getGroupedItemRows($tableId, $unitId, $header['group_key']);
                $groupItemsForSum = [];

                foreach ($itemRows as $itemRow) {

                    $cells = [];
                    foreach ($columns as $col) {
                        $raw = $dataMap[$itemRow['id']][$col['id']] ?? null;
                        $cells[$col['id']] = $col['data_type'] === 'text' ? ($raw['text_value'] ?? null) : ($raw['value'] ?? null);
                    }

                    $rows[] = [
                        'type'      => 'item',
                        'id'        => $itemRow['id'],
                        'label'     => $itemRow['label'],
                        'group_key' => $header['group_key'],
                        'cells'     => $cells,
                    ];

                    $groupItemsForSum[] = $cells;
                }

                if (isset($totalDefs[$header['group_key']])) {

                    $subtotalCells = [];

                    foreach ($columns as $col) {

                        $mode = $col['total_mode'] ?? ($col['is_summable'] ? 'sum' : 'none');

                        if ($mode !== 'sum') {
                            $subtotalCells[$col['id']] = null;
                            continue;
                        }

                        $sum = 0;
                        $has = false;

                        foreach ($groupItemsForSum as $c) {
                            if ($c[$col['id']] !== null) {
                                $sum += (float) $c[$col['id']];
                                $has = true;
                            }
                        }

                        $subtotalCells[$col['id']] = $has ? round($sum, 1) : null;
                    }

                    $rows[] = [
                        'type'      => 'total',
                        'label'     => $totalDefs[$header['group_key']],
                        'group_key' => $header['group_key'],
                        'cells'     => $subtotalCells,
                    ];
                }
            }

        } else {

            $rowDefsRaw = $this->repository()->getRows($tableId, $unitId);

            $rawData = $this->repository()->getData($tableId, $unitId);
            $dataMap = [];
            foreach ($rawData as $d) {
                $dataMap[$d['row_id']][$d['column_id']] = ['value' => $d['value'], 'text_value' => $d['text_value']];
            }

            foreach ($rowDefsRaw as $rowDef) {

                $cells = [];
                foreach ($columns as $col) {
                    $raw = $dataMap[$rowDef['id']][$col['id']] ?? null;
                    $cells[$col['id']] = $col['data_type'] === 'text' ? ($raw['text_value'] ?? null) : ($raw['value'] ?? null);
                }

                $rows[] = ['type' => 'item', 'id' => $rowDef['id'], 'label' => $rowDef['label'], 'cells' => $cells];
            }
        }

        $totalRow = [];

foreach ($columns as $col) {

            $mode = $col['total_mode'] ?? ($col['is_summable'] ? 'sum' : 'none');

            $values = [];

            foreach ($rows as $r) {
                if (($r['type'] ?? 'item') !== 'item') {
                    continue;
                }
                if (isset($r['cells'][$col['id']]) && $r['cells'][$col['id']] !== null) {
                    $values[] = (float) $r['cells'][$col['id']];
                }
            }

            if ($mode === 'none' || empty($values)) {
                $totalRow[$col['id']] = $mode === 'none' ? null : 0;
                continue;
            }

            switch ($mode) {
                case 'average':
                    $totalRow[$col['id']] = round(array_sum($values) / count($values), 2);
                    break;
                case 'min':
                    $totalRow[$col['id']] = min($values);
                    break;
                case 'max':
                    $totalRow[$col['id']] = max($values);
                    break;
                default:
                    $totalRow[$col['id']] = array_sum($values);
            }
        }

$averageRow = [];

        if ((int) ($table['show_average_row'] ?? 0) === 1) {

            foreach ($columns as $col) {

                $mode = $col['total_mode'] ?? ($col['is_summable'] ? 'sum' : 'none');

                if ($mode !== 'sum') {
                    $averageRow[$col['id']] = null;
                    continue;
                }

                $values = [];

                foreach ($rows as $r) {
                    if (isset($r['cells'][$col['id']]) && $r['cells'][$col['id']] !== null) {
                        $values[] = (float) $r['cells'][$col['id']];
                    }
                }

                $averageRow[$col['id']] = empty($values) ? null : round(array_sum($values) / count($values), 2);
            }
        }

        $documents = $this->repository()->getDocuments($tableId, $unitId);

        return $this->success([
            'table'     => $table,
            'columns'   => $columns,
            'rows'      => $rows,
            'total'     => $totalRow,
            'average'   => $averageRow,
            'documents' => $documents,
        ]);
    }

public function saveData(string $code, int $unitId, array $cells, int $updatedBy): array
    {
        try {

            foreach ($cells as $rowId => $columnValues) {

                $rowId = (int) $rowId;

                if ($rowId <= 0) {
                    continue;
                }

                foreach ($columnValues as $columnId => $value) {

                    $columnId = (int) $columnId;
                    $dataType = $this->repository()->getColumnDataType($columnId);
                    $rawValue = trim((string) $value);

                    $this->repository()->saveCell($rowId, $columnId, $rawValue, $dataType, $updatedBy);
                }
            }

            return $this->success(null, 'Data berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function addRow(string $code, int $unitId, string $label): array
    {
        try {

            $table = $this->repository()->getTableByCode($code);

            if (!$table) {
                throw new InvalidArgumentException('Tabel tidak ditemukan.');
            }

            $label = trim($label);

            if ($label === '') {
                throw new InvalidArgumentException('Label Tahun wajib diisi.');
            }

            $rowId = $this->repository()->createRow((int) $table['id'], $unitId, $label);

            return $this->success(['id' => $rowId], 'Baris Tahun berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateRowLabel(int $rowId, string $label): array
    {
        try {

            $label = trim($label);

            if ($label === '') {
                throw new InvalidArgumentException('Label Tahun wajib diisi.');
            }

            $this->repository()->updateRowLabel($rowId, $label);

            return $this->success(null, 'Label Tahun berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteRow(int $rowId): array
    {
        $this->repository()->deleteRow($rowId);

        return $this->success(null, 'Baris Tahun berhasil dihapus.');
    }

    public function updateTableLabels(int $tableId, array $input): array
    {
        try {

            $labelTs2 = trim($input['label_ts2'] ?? '') ?: null;
            $labelTs1 = trim($input['label_ts1'] ?? '') ?: null;
            $labelTs = trim($input['label_ts'] ?? '') ?: null;

            $this->repository()->updateTableLabels($tableId, $labelTs2, $labelTs1, $labelTs);

            return $this->success(null, 'Label Tahun Akademik berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function uploadDocument(int $tableId, int $unitId, array $input, array $file, int $uploadedBy): array
    {
        try {

            $documentName = trim($input['document_name'] ?? '');
            $linkUrl = trim($input['link_url'] ?? '');
            $hasFile = !empty($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if ($documentName === '') {
                throw new InvalidArgumentException('Nama Dokumen wajib diisi.');
            }

            if (!$hasFile && $linkUrl === '') {
                throw new InvalidArgumentException('Isi salah satu: Upload File atau Link.');
            }

            if ($linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Format Link tidak valid.');
            }

            $filePath = null;
            $fileOriginalName = null;

            if ($hasFile) {

                require_once __DIR__ . '/../core/UploadHelper.php';

                $uploader = new UploadHelper(
                    dirname(__DIR__) . '/uploads/acc_documents',
                    ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
                    [
                        'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg', 'image/png',
                    ]
                );

                $result = $uploader->upload($file);

                $filePath = $result['document_file'];
                $fileOriginalName = $result['document_original_name'];
            }

            $this->repository()->createDocument([
                'table_id'            => $tableId,
                'unit_id'             => $unitId,
                'document_name'       => $documentName,
                'file_path'           => $filePath,
                'file_original_name'  => $fileOriginalName,
                'link_url'            => $linkUrl !== '' ? $linkUrl : null,
                'uploaded_by'         => $uploadedBy,
            ]);

            return $this->success(null, 'Dokumen Pendukung berhasil diunggah.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteDocument(int $id): array
    {
        $this->repository()->deleteDocument($id);

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
    public function getCriteria(): array
    {
        return $this->success($this->repository()->getCriteria());
    }

    public function getTablesByCriteria(int $criteriaId, int $unitId = 0): array
    {
        return $this->success($this->repository()->getTablesByCriteria($criteriaId, $unitId));
    }

    public function updateTableUnit(int $tableId, int $unitId): array
    {
        $this->repository()->updateTableUnit($tableId, $unitId > 0 ? $unitId : null);

        return $this->success(null, 'Unit Kerja pemilik Tabel berhasil disimpan.');
    }

    public function getDocumentChecklist(int $criteriaId, int $unitId, string $semester, string $academicYear): array
    {
        return $this->success($this->repository()->getDocumentsWithUploads($criteriaId, $unitId, $semester, $academicYear));
    }

    public function uploadDocumentFile(int $documentId, int $unitId, string $semester, string $academicYear, array $input, array $file, int $uploadedBy): array
    {
        try {

            $linkUrl = trim($input['link_url'] ?? '');
            $hasFile = !empty($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            if (!in_array($semester, ['Ganjil', 'Genap'], true)) {
                throw new InvalidArgumentException('Semester tidak valid.');
            }

            if ($academicYear === '') {
                throw new InvalidArgumentException('Tahun Akademik wajib diisi.');
            }

            if (!$hasFile && $linkUrl === '') {
                throw new InvalidArgumentException('Isi salah satu: Upload File atau Link.');
            }

            if ($linkUrl !== '' && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Format Link tidak valid.');
            }

            $filePath = null;
            $fileOriginalName = null;

            if ($hasFile) {

                require_once __DIR__ . '/../core/UploadHelper.php';

                $uploader = new UploadHelper(
                    dirname(__DIR__) . '/uploads/acc_documents',
                    ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
                    [
                        'application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg', 'image/png',
                    ]
                );

                $result = $uploader->upload($file);

                $filePath = $result['document_file'];
                $fileOriginalName = $result['document_original_name'];
            }

            $this->repository()->saveDocumentUpload([
                'document_id'         => $documentId,
                'unit_id'             => $unitId,
                'semester'            => $semester,
                'academic_year'       => $academicYear,
                'file_path'           => $filePath,
                'file_original_name'  => $fileOriginalName,
                'link_url'            => $linkUrl !== '' ? $linkUrl : null,
                'uploaded_by'         => $uploadedBy,
            ]);

            return $this->success(null, 'Dokumen berhasil diunggah.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteDocumentUpload(int $id): array
    {
        $this->repository()->deleteDocumentUpload($id);

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
    public function getAcademicYears(): array
    {
        return $this->success($this->repository()->getAcademicYears());
    }
    public function addDynamicColumn(string $code, int $unitId, string $label): array
    {
        try {

            $table = $this->repository()->getTableByCode($code);

            if (!$table) {
                throw new InvalidArgumentException('Tabel tidak ditemukan.');
            }

            if ((int) ($table['allow_dynamic_columns'] ?? 0) !== 1) {
                throw new InvalidArgumentException('Tabel ini tidak mendukung penambahan kolom.');
            }

            $label = trim($label);

            if ($label === '') {
                throw new InvalidArgumentException('Nama Kolom wajib diisi.');
            }

            $newId = $this->repository()->createDynamicColumn(
                (int) $table['id'],
                $unitId,
                $table['dynamic_column_group_label'] ?? null,
                $label
            );

            return $this->success(['id' => $newId], 'Kolom berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function deleteDynamicColumn(int $id, int $unitId): array
    {
        $this->repository()->deleteDynamicColumn($id, $unitId);

        return $this->success(null, 'Kolom berhasil dihapus.');
    }

    public function addGroupedRow(string $code, int $unitId, string $groupKey, string $label): array
    {
        try {

            $table = $this->repository()->getTableByCode($code);

            if (!$table) {
                throw new InvalidArgumentException('Tabel tidak ditemukan.');
            }

            $label = trim($label);

            if ($label === '') {
                throw new InvalidArgumentException('Label wajib diisi.');
            }

            $rowId = $this->repository()->createGroupedRow((int) $table['id'], $unitId, $groupKey, $label);

            return $this->success(['id' => $rowId], 'Baris berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getPendingCount(int $unitId): array
    {
        return $this->success($this->repository()->getPendingCount($unitId));
    }
    public function getCriteriaProgress(int $unitId): array
    {
        return $this->success($this->repository()->getCriteriaProgress($unitId));
    }

    public function getCriteriaProgressByYear(int $unitId): array
    {
        return $this->success($this->repository()->getCriteriaProgressByYear($unitId));
    }
}