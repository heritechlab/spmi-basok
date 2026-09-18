<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class AccRepository extends BaseRepository
{
    protected string $table = 'acc_tables';

    public function getAllTables(): array
    {
        $stmt = $this->prepare("SELECT id, code, title, description FROM acc_tables WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getTableByCode(string $code): ?array
    {
        $stmt = $this->prepare("SELECT id, criteria_id, code, title, description, label_ts2, label_ts1, label_ts, allow_dynamic_columns, dynamic_column_group_label, unit_id, row_mode, row_header_label, show_average_row FROM acc_tables WHERE code = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("s", $code);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

public function getTableColumns(int $tableId, int $unitId = 0): array
    {
        $stmt = $this->prepare("
            SELECT id, super_group_label, group_label, label, data_type, is_summable, total_mode, footnote_code, formula, unit_id, is_currency
            FROM acc_table_columns
            WHERE table_id = ? AND is_active = 1 AND (unit_id IS NULL OR unit_id = ?)
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("ii", $tableId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createDynamicColumn(int $tableId, int $unitId, ?string $groupLabel, string $label): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_table_columns WHERE table_id = ? AND unit_id = ?");
        $stmt->bind_param("ii", $tableId, $unitId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("
            INSERT INTO acc_table_columns (table_id, unit_id, group_label, label, data_type, is_summable, total_mode, sort_order, is_active)
            VALUES (?, ?, ?, ?, 'integer', 0, 'none', ?, 1)
        ");
        $stmt2->bind_param("iissi", $tableId, $unitId, $groupLabel, $label, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function deleteDynamicColumn(int $id, int $unitId): void
    {
        $stmt = $this->prepare("DELETE FROM acc_table_data WHERE column_id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        $stmt2 = $this->prepare("DELETE FROM acc_table_columns WHERE id = ? AND unit_id = ?");
        $stmt2->bind_param("ii", $id, $unitId);
        $this->execute($stmt2);
    }

    public function getRows(int $tableId, int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT id, label, sort_order
            FROM acc_table_rows
            WHERE table_id = ? AND unit_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("ii", $tableId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getGroupTotalDefs(int $tableId): array
    {
        $stmt = $this->prepare("SELECT group_key, label FROM acc_table_row_defs WHERE table_id = ? AND row_type = 'total' AND is_active = 1");
        $stmt->bind_param("i", $tableId);
        $this->execute($stmt);

        $result = [];
        foreach ($this->fetchAll($stmt) as $row) {
            $result[$row['group_key']] = $row['label'];
        }

        return $result;
    }

    public function getGroupHeaders(int $tableId): array
    {
        $stmt = $this->prepare("SELECT id, label, group_key FROM acc_table_row_defs WHERE table_id = ? AND row_type = 'header' AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->bind_param("i", $tableId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getGroupedItemRows(int $tableId, int $unitId, string $groupKey): array
    {
        $stmt = $this->prepare("SELECT id, label FROM acc_table_rows WHERE table_id = ? AND unit_id = ? AND group_key = ? ORDER BY sort_order ASC");
        $stmt->bind_param("iis", $tableId, $unitId, $groupKey);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createGroupedRow(int $tableId, int $unitId, string $groupKey, string $label): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_table_rows WHERE table_id = ? AND unit_id = ? AND group_key = ?");
        $stmt->bind_param("iis", $tableId, $unitId, $groupKey);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO acc_table_rows (table_id, unit_id, label, sort_order, group_key) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("iisis", $tableId, $unitId, $label, $nextOrder, $groupKey);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function getRowDefs(int $tableId): array
    {
        $stmt = $this->prepare("SELECT id, label, row_type, group_key FROM acc_table_row_defs WHERE table_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $stmt->bind_param("i", $tableId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getOrCreateFixedRows(int $tableId, int $unitId, array $rowDefs): array
    {
        $result = [];

        foreach ($rowDefs as $def) {

            if ($def['row_type'] !== 'item') {
                continue;
            }

            $stmt = $this->prepare("SELECT id, label FROM acc_table_rows WHERE table_id = ? AND unit_id = ? AND row_def_id = ? LIMIT 1");
            $stmt->bind_param("iii", $tableId, $unitId, $def['id']);
            $this->execute($stmt);
            $existing = $this->fetchOne($stmt);

            if ($existing) {
                $result[$def['id']] = $existing;
                continue;
            }

            $label = $def['label'];

            $stmt2 = $this->prepare("
                INSERT INTO acc_table_rows (table_id, unit_id, label, sort_order, row_def_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $sortOrder = count($result) + 1;
            $stmt2->bind_param("iisii", $tableId, $unitId, $label, $sortOrder, $def['id']);
            $this->execute($stmt2);

            $result[$def['id']] = ['id' => $this->insertId(), 'label' => $label];
        }

        return $result;
    }

    public function createRow(int $tableId, int $unitId, string $label): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_table_rows WHERE table_id = ? AND unit_id = ?");
        $stmt->bind_param("ii", $tableId, $unitId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO acc_table_rows (table_id, unit_id, label, sort_order) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("iisi", $tableId, $unitId, $label, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateRowLabel(int $rowId, string $label): void
    {
        $stmt = $this->prepare("UPDATE acc_table_rows SET label = ? WHERE id = ?");
        $stmt->bind_param("si", $label, $rowId);
        $this->execute($stmt);
    }

    public function deleteRow(int $rowId): void
    {
        $stmt = $this->prepare("DELETE FROM acc_table_data WHERE row_id = ?");
        $stmt->bind_param("i", $rowId);
        $this->execute($stmt);

        $stmt2 = $this->prepare("DELETE FROM acc_table_rows WHERE id = ?");
        $stmt2->bind_param("i", $rowId);
        $this->execute($stmt2);
    }

    public function getData(int $tableId, int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT d.row_id, d.column_id, d.value, d.text_value
            FROM acc_table_data d
            JOIN acc_table_rows r ON r.id = d.row_id
            WHERE r.table_id = ? AND r.unit_id = ?
        ");
        $stmt->bind_param("ii", $tableId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function saveCell(int $rowId, int $columnId, ?string $rawValue, string $dataType, int $updatedBy): void
    {
        if (in_array($dataType, ['integer', 'decimal'], true)) {

            $value = ($rawValue === null || $rawValue === '') ? null : (float) $rawValue;
            $textValue = null;

        } elseif ($dataType === 'checkbox') {

            $value = ($rawValue === '1' || $rawValue === 'true' || $rawValue === 'on') ? 1 : 0;
            $textValue = null;

        } else {

            $value = null;
            $textValue = ($rawValue === null || $rawValue === '') ? null : $rawValue;
        }

        $stmt = $this->prepare("
            INSERT INTO acc_table_data (row_id, column_id, value, text_value, updated_by)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value), text_value = VALUES(text_value), updated_by = VALUES(updated_by)
        ");
        $stmt->bind_param("iidsi", $rowId, $columnId, $value, $textValue, $updatedBy);
        $this->execute($stmt);
    }

    public function getColumnDataType(int $columnId): string
    {
        $stmt = $this->prepare("SELECT data_type FROM acc_table_columns WHERE id = ?");
        $stmt->bind_param("i", $columnId);
        $this->execute($stmt);

        return $this->fetchOne($stmt)['data_type'] ?? 'integer';
    }

    public function getDocuments(int $tableId, int $unitId): array
    {
        $stmt = $this->prepare("
            SELECT d.id, d.document_name, d.file_path, d.file_original_name, d.link_url, d.uploaded_at, u.full_name AS uploaded_by_name
            FROM acc_table_documents d
            LEFT JOIN users u ON u.id = d.uploaded_by
            WHERE d.table_id = ? AND d.unit_id = ?
            ORDER BY d.uploaded_at DESC
        ");
        $stmt->bind_param("ii", $tableId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createDocument(array $data): int
    {
        $stmt = $this->prepare("
            INSERT INTO acc_table_documents (table_id, unit_id, document_name, file_path, file_original_name, link_url, uploaded_by, uploaded_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param(
            "iissssi",
            $data['table_id'],
            $data['unit_id'],
            $data['document_name'],
            $data['file_path'],
            $data['file_original_name'],
            $data['link_url'],
            $data['uploaded_by']
        );
        $this->execute($stmt);

        return $this->insertId();
    }

    public function deleteDocument(int $id): void
    {
        $stmt = $this->prepare("DELETE FROM acc_table_documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }

    public function updateTableLabels(int $tableId, ?string $labelTs2, ?string $labelTs1, ?string $labelTs): void
    {
        $stmt = $this->prepare("UPDATE acc_tables SET label_ts2 = ?, label_ts1 = ?, label_ts = ? WHERE id = ?");
        $stmt->bind_param("sssi", $labelTs2, $labelTs1, $labelTs, $tableId);
        $this->execute($stmt);
    }

    public function updateTableUnit(int $tableId, ?int $unitId): void
    {
        $stmt = $this->prepare("UPDATE acc_tables SET unit_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $unitId, $tableId);
        $this->execute($stmt);
    }

    public function getProdiUnits(): array
    {
        $stmt = $this->prepare("SELECT id, code, name FROM units WHERE type = 'Program Studi' ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAllUnits(): array
    {
        $stmt = $this->prepare("SELECT id, code, name, type FROM units ORDER BY name ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getCriteria(): array
    {
        $stmt = $this->prepare("SELECT id, name FROM acc_criteria WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getAcademicYears(): array
    {
        $stmt = $this->prepare("SELECT id, label FROM acc_academic_years WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

public function getTablesByCriteria(int $criteriaId, int $unitId = 0): array
    {
        $stmt = $this->prepare("
            SELECT t.id, t.code, t.title, t.description
            FROM acc_tables t
            WHERE t.criteria_id = ? AND t.is_active = 1
              AND (
                  NOT EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id)
                  OR EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id AND tu.unit_id = ?)
              )
            ORDER BY t.sort_order ASC
        ");
        $stmt->bind_param("ii", $criteriaId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getTableUnitIds(int $tableId): array
    {
        $stmt = $this->prepare("SELECT unit_id FROM acc_table_units WHERE table_id = ?");
        $stmt->bind_param("i", $tableId);
        $this->execute($stmt);

        return array_column($this->fetchAll($stmt), 'unit_id');
    }

    public function saveTableUnits(int $tableId, array $unitIds): void
    {
        $del = $this->prepare("DELETE FROM acc_table_units WHERE table_id = ?");
        $del->bind_param("i", $tableId);
        $this->execute($del);

        if (empty($unitIds)) {
            return;
        }

        $ins = $this->prepare("INSERT INTO acc_table_units (table_id, unit_id) VALUES (?, ?)");

        foreach ($unitIds as $unitId) {
            $unitId = (int) $unitId;
            $ins->bind_param("ii", $tableId, $unitId);
            $this->execute($ins);
        }
    }

public function getDocumentsWithUploads(int $criteriaId, int $unitId, string $semester, string $academicYear): array
    {
        $stmt = $this->prepare("
            SELECT d.id, d.document_name,
                   u.id AS upload_id, u.file_path, u.file_original_name, u.link_url, u.uploaded_at
            FROM acc_documents d
            LEFT JOIN acc_document_uploads u
                ON u.document_id = d.id AND u.unit_id = ? AND u.semester = ? AND u.academic_year = ?
            WHERE d.criteria_id = ? AND d.is_active = 1
              AND (
                  NOT EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id)
                  OR EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id AND du.unit_id = ?)
              )
            ORDER BY d.sort_order ASC
        ");
        $stmt->bind_param("issii", $unitId, $semester, $academicYear, $criteriaId, $unitId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function getDocumentUnitIds(int $documentId): array
    {
        $stmt = $this->prepare("SELECT unit_id FROM acc_document_units WHERE document_id = ?");
        $stmt->bind_param("i", $documentId);
        $this->execute($stmt);

        return array_column($this->fetchAll($stmt), 'unit_id');
    }

    public function saveDocumentUnits(int $documentId, array $unitIds): void
    {
        $del = $this->prepare("DELETE FROM acc_document_units WHERE document_id = ?");
        $del->bind_param("i", $documentId);
        $this->execute($del);

        if (empty($unitIds)) {
            return;
        }

        $ins = $this->prepare("INSERT INTO acc_document_units (document_id, unit_id) VALUES (?, ?)");

        foreach ($unitIds as $unitId) {
            $unitId = (int) $unitId;
            $ins->bind_param("ii", $documentId, $unitId);
            $this->execute($ins);
        }
    }

    public function saveDocumentUpload(array $data): void
    {
        $stmt = $this->prepare("
            INSERT INTO acc_document_uploads (document_id, unit_id, semester, academic_year, file_path, file_original_name, link_url, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                file_path = VALUES(file_path),
                file_original_name = VALUES(file_original_name),
                link_url = VALUES(link_url),
                uploaded_by = VALUES(uploaded_by)
        ");
        $stmt->bind_param(
            "iisssssi",
            $data['document_id'],
            $data['unit_id'],
            $data['semester'],
            $data['academic_year'],
            $data['file_path'],
            $data['file_original_name'],
            $data['link_url'],
            $data['uploaded_by']
        );
        $this->execute($stmt);
    }

    public function deleteDocumentUpload(int $id): void
    {
        $stmt = $this->prepare("DELETE FROM acc_document_uploads WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }

 public function getCriteriaProgressByYear(int $unitId): array
    {
        $stmt = $this->prepare("SELECT label FROM acc_academic_years WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->execute($stmt);
        $years = array_column($this->fetchAll($stmt), 'label');

        $criteriaList = $this->getCriteria();
        $result = [];

        foreach ($criteriaList as $crit) {

            $criteriaId = (int) $crit['id'];

            $stmt = $this->prepare("
                SELECT t.id
                FROM acc_tables t
                WHERE t.is_active = 1 AND t.criteria_id = ? AND t.row_mode = 'dynamic'
                  AND (
                      NOT EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id)
                      OR EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id AND tu.unit_id = ?)
                  )
            ");
            $stmt->bind_param("ii", $criteriaId, $unitId);
            $this->execute($stmt);
            $dynamicTableIds = array_column($this->fetchAll($stmt), 'id');

            $stmt = $this->prepare("
                SELECT t.id
                FROM acc_tables t
                WHERE t.is_active = 1 AND t.criteria_id = ? AND t.row_mode != 'dynamic'
                  AND (
                      NOT EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id)
                      OR EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id AND tu.unit_id = ?)
                  )
            ");
            $stmt->bind_param("ii", $criteriaId, $unitId);
            $this->execute($stmt);
            $fixedTableIds = array_column($this->fetchAll($stmt), 'id');

            $stmt = $this->prepare("
                SELECT d.id
                FROM acc_documents d
                WHERE d.is_active = 1 AND d.criteria_id = ?
                  AND (
                      NOT EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id)
                      OR EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id AND du.unit_id = ?)
                  )
            ");
            $stmt->bind_param("ii", $criteriaId, $unitId);
            $this->execute($stmt);
            $documentIds = array_column($this->fetchAll($stmt), 'id');

            $tableYearProgress = [];
            $documentYearProgress = [];

            foreach ($years as $year) {

                // ===== Tabel Data Dukung (dinamis) =====
                $tableFilled = 0;

                foreach ($dynamicTableIds as $tableId) {

                    $stmt2 = $this->prepare("
                        SELECT COUNT(*) AS total
                        FROM acc_table_rows r
                        JOIN acc_table_data d ON d.row_id = r.id
                        WHERE r.table_id = ? AND r.unit_id = ? AND r.label = ?
                          AND (d.value IS NOT NULL OR d.text_value IS NOT NULL)
                    ");
                    $stmt2->bind_param("iis", $tableId, $unitId, $year);
                    $this->execute($stmt2);

                    if ((int) ($this->fetchOne($stmt2)['total'] ?? 0) > 0) {
                        $tableFilled++;
                    }
                }

                $tableTotal = count($dynamicTableIds);
                $tablePercent = $tableTotal > 0 ? (int) round(($tableFilled / $tableTotal) * 100) : null;

                $tableYearProgress[] = [
                    'year'    => $year,
                    'total'   => $tableTotal,
                    'filled'  => $tableFilled,
                    'percent' => $tablePercent,
                ];

// ===== Daftar Dokumen (dihitung per Semester: Ganjil & Genap) =====
                $docFilled = 0;
                $semesters = ['Ganjil', 'Genap'];

                foreach ($documentIds as $docId) {

                    foreach ($semesters as $semester) {

                        $stmt3 = $this->prepare("
                            SELECT COUNT(*) AS total
                            FROM acc_document_uploads
                            WHERE document_id = ? AND unit_id = ? AND semester = ? AND academic_year = ?
                        ");
                        $stmt3->bind_param("iiss", $docId, $unitId, $semester, $year);
                        $this->execute($stmt3);

                        if ((int) ($this->fetchOne($stmt3)['total'] ?? 0) > 0) {
                            $docFilled++;
                        }
                    }
                }

                $docTotal = count($documentIds) * 2;
                $docPercent = $docTotal > 0 ? (int) round(($docFilled / $docTotal) * 100) : null;

                $documentYearProgress[] = [
                    'year'    => $year,
                    'total'   => $docTotal,
                    'filled'  => $docFilled,
                    'percent' => $docPercent,
                ];
            }

            // ===== Tabel Baris Tetap (non-tahun) =====
            $fixedFilled = 0;

            foreach ($fixedTableIds as $tableId) {

                $stmt4 = $this->prepare("
                    SELECT COUNT(*) AS total
                    FROM acc_table_rows r
                    JOIN acc_table_data d ON d.row_id = r.id
                    WHERE r.table_id = ? AND r.unit_id = ?
                      AND (d.value IS NOT NULL OR d.text_value IS NOT NULL)
                ");
                $stmt4->bind_param("ii", $tableId, $unitId);
                $this->execute($stmt4);

                if ((int) ($this->fetchOne($stmt4)['total'] ?? 0) > 0) {
                    $fixedFilled++;
                }
            }

            $result[] = [
                'criteria_id'    => $criteriaId,
                'criteria_name'  => $crit['name'],
                'tables_by_year' => $tableYearProgress,
                'docs_by_year'   => $documentYearProgress,
                'fixed_total'    => count($fixedTableIds),
                'fixed_filled'   => $fixedFilled,
            ];
        }

        return $result;
    }

    public function getCriteriaProgress(int $unitId): array
    {
        $stmt = $this->prepare("SELECT label FROM acc_academic_years WHERE is_active = 1 ORDER BY sort_order DESC LIMIT 1");
        $this->execute($stmt);
        $latestYearRow = $this->fetchOne($stmt);
        $latestYear = $latestYearRow['label'] ?? null;

        $criteriaList = $this->getCriteria();
        $progress = [];

        foreach ($criteriaList as $crit) {

            $criteriaId = (int) $crit['id'];
            $total = 0;
            $filled = 0;

            // ===== Tabel Data Dukung =====
            $stmt = $this->prepare("
                SELECT t.id, t.row_mode
                FROM acc_tables t
                WHERE t.is_active = 1 AND t.criteria_id = ?
                  AND (
                      NOT EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id)
                      OR EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id AND tu.unit_id = ?)
                  )
            ");
            $stmt->bind_param("ii", $criteriaId, $unitId);
            $this->execute($stmt);
            $tables = $this->fetchAll($stmt);

            foreach ($tables as $t) {

                $total++;
                $tableId = (int) $t['id'];

                if ($t['row_mode'] === 'dynamic' && $latestYear) {
                    $stmt2 = $this->prepare("
                        SELECT COUNT(*) AS total
                        FROM acc_table_rows r
                        JOIN acc_table_data d ON d.row_id = r.id
                        WHERE r.table_id = ? AND r.unit_id = ? AND r.label = ?
                          AND (d.value IS NOT NULL OR d.text_value IS NOT NULL)
                    ");
                    $stmt2->bind_param("iis", $tableId, $unitId, $latestYear);
                } else {
                    $stmt2 = $this->prepare("
                        SELECT COUNT(*) AS total
                        FROM acc_table_rows r
                        JOIN acc_table_data d ON d.row_id = r.id
                        WHERE r.table_id = ? AND r.unit_id = ?
                          AND (d.value IS NOT NULL OR d.text_value IS NOT NULL)
                    ");
                    $stmt2->bind_param("ii", $tableId, $unitId);
                }

                $this->execute($stmt2);
                $has = (int) ($this->fetchOne($stmt2)['total'] ?? 0) > 0;

                if ($has) {
                    $filled++;
                }
            }

            // ===== Daftar Dokumen =====
            if ($latestYear) {

                $stmt3 = $this->prepare("
                    SELECT d.id
                    FROM acc_documents d
                    WHERE d.is_active = 1 AND d.criteria_id = ?
                      AND (
                          NOT EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id)
                          OR EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id AND du.unit_id = ?)
                      )
                ");
                $stmt3->bind_param("ii", $criteriaId, $unitId);
                $this->execute($stmt3);
                $documents = $this->fetchAll($stmt3);

                foreach ($documents as $doc) {

                    $total++;
                    $docId = (int) $doc['id'];

                    $stmt4 = $this->prepare("
                        SELECT COUNT(*) AS total
                        FROM acc_document_uploads
                        WHERE document_id = ? AND unit_id = ? AND academic_year = ?
                    ");
                    $stmt4->bind_param("iis", $docId, $unitId, $latestYear);
                    $this->execute($stmt4);
                    $has = (int) ($this->fetchOne($stmt4)['total'] ?? 0) > 0;

                    if ($has) {
                        $filled++;
                    }
                }
            }

            $percent = $total > 0 ? (int) round(($filled / $total) * 100) : 0;

            $progress[] = [
                'criteria_id'   => $criteriaId,
                'criteria_name' => $crit['name'],
                'total'         => $total,
                'filled'        => $filled,
                'percent'       => $percent,
            ];
        }

        return $progress;
    }

    public function getPendingCount(int $unitId): array
    {
        // Tahun Akademik terbaru
        $stmt = $this->prepare("SELECT label FROM acc_academic_years WHERE is_active = 1 ORDER BY sort_order DESC LIMIT 1");
        $this->execute($stmt);
        $latestYearRow = $this->fetchOne($stmt);
        $latestYear = $latestYearRow['label'] ?? null;

        $pendingTables = 0;
        $pendingDocuments = 0;

        // ===== Tabel Data Dukung =====
        $stmt = $this->prepare("
            SELECT t.id, t.row_mode
            FROM acc_tables t
            WHERE t.is_active = 1
              AND (
                  NOT EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id)
                  OR EXISTS (SELECT 1 FROM acc_table_units tu WHERE tu.table_id = t.id AND tu.unit_id = ?)
              )
        ");
        $stmt->bind_param("i", $unitId);
        $this->execute($stmt);
        $tables = $this->fetchAll($stmt);

        foreach ($tables as $t) {

            $tableId = (int) $t['id'];

            if ($t['row_mode'] === 'dynamic' && $latestYear) {

                $stmt2 = $this->prepare("
                    SELECT COUNT(*) AS total
                    FROM acc_table_rows r
                    JOIN acc_table_data d ON d.row_id = r.id
                    WHERE r.table_id = ? AND r.unit_id = ? AND r.label = ?
                      AND (d.value IS NOT NULL OR d.text_value IS NOT NULL)
                ");
                $stmt2->bind_param("iis", $tableId, $unitId, $latestYear);
                $this->execute($stmt2);
                $filled = (int) ($this->fetchOne($stmt2)['total'] ?? 0);

                if ($filled === 0) {
                    $pendingTables++;
                }

            } else {

                $stmt2 = $this->prepare("
                    SELECT COUNT(*) AS total
                    FROM acc_table_rows r
                    JOIN acc_table_data d ON d.row_id = r.id
                    WHERE r.table_id = ? AND r.unit_id = ?
                      AND (d.value IS NOT NULL OR d.text_value IS NOT NULL)
                ");
                $stmt2->bind_param("ii", $tableId, $unitId);
                $this->execute($stmt2);
                $filled = (int) ($this->fetchOne($stmt2)['total'] ?? 0);

                if ($filled === 0) {
                    $pendingTables++;
                }
            }
        }

        // ===== Daftar Dokumen =====
        if ($latestYear) {

            $stmt3 = $this->prepare("
                SELECT d.id
                FROM acc_documents d
                WHERE d.is_active = 1
                  AND (
                      NOT EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id)
                      OR EXISTS (SELECT 1 FROM acc_document_units du WHERE du.document_id = d.id AND du.unit_id = ?)
                  )
            ");
            $stmt3->bind_param("i", $unitId);
            $this->execute($stmt3);
            $documents = $this->fetchAll($stmt3);

            foreach ($documents as $doc) {

                $stmt4 = $this->prepare("
                    SELECT COUNT(*) AS total
                    FROM acc_document_uploads
                    WHERE document_id = ? AND unit_id = ? AND academic_year = ?
                ");
                $docId = (int) $doc['id'];
                $stmt4->bind_param("iis", $docId, $unitId, $latestYear);
                $this->execute($stmt4);
                $uploaded = (int) ($this->fetchOne($stmt4)['total'] ?? 0);

                if ($uploaded === 0) {
                    $pendingDocuments++;
                }
            }
        }

        return [
            'pending_tables'    => $pendingTables,
            'pending_documents' => $pendingDocuments,
            'total'             => $pendingTables + $pendingDocuments,
            'latest_year'       => $latestYear,
        ];
    }
    
}