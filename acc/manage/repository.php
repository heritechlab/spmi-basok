<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseRepository.php';

class AccManageRepository extends BaseRepository
{
    protected string $table = 'acc_tables';

    public function createTable(string $code, string $title, string $description): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_tables");
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO acc_tables (code, title, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt2->bind_param("sssi", $code, $title, $description, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateTable(int $id, string $title, string $description): void
    {
        $stmt = $this->prepare("UPDATE acc_tables SET title = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $title, $description, $id);
        $this->execute($stmt);
    }

    public function deleteTable(int $id): void
    {
        $stmt = $this->prepare("UPDATE acc_tables SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }

    public function codeExists(string $code): bool
    {
        $stmt = $this->prepare("SELECT COUNT(*) AS total FROM acc_tables WHERE code = ?");
        $stmt->bind_param("s", $code);
        $this->execute($stmt);

        return ((int) ($this->fetchOne($stmt)['total'] ?? 0)) > 0;
    }

public function createColumn(int $tableId, ?string $groupLabel, string $label, string $dataType, string $totalMode): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_table_columns WHERE table_id = ?");
        $stmt->bind_param("i", $tableId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $isSummableInt = $totalMode === 'sum' ? 1 : 0;

        $stmt2 = $this->prepare("
            INSERT INTO acc_table_columns (table_id, group_label, label, data_type, is_summable, total_mode, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt2->bind_param("issssii", $tableId, $groupLabel, $label, $dataType, $isSummableInt, $totalMode, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateColumn(int $id, ?string $groupLabel, string $label, string $dataType, string $totalMode): void
    {
        $isSummableInt = $totalMode === 'sum' ? 1 : 0;

        $stmt = $this->prepare("UPDATE acc_table_columns SET group_label = ?, label = ?, data_type = ?, is_summable = ?, total_mode = ? WHERE id = ?");
        $stmt->bind_param("sssisi", $groupLabel, $label, $dataType, $isSummableInt, $totalMode, $id);
        $this->execute($stmt);
    }

    public function deleteColumn(int $id): void
    {
        $stmt = $this->prepare("UPDATE acc_table_columns SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }
    /*
    |--------------------------------------------------------------------------
    | CRUD: Tahun Akademik
    |--------------------------------------------------------------------------
    */

    public function createAcademicYear(string $label): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_academic_years");
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO acc_academic_years (label, sort_order, is_active) VALUES (?, ?, 1)");
        $stmt2->bind_param("si", $label, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function deleteAcademicYear(int $id): void
    {
        $stmt = $this->prepare("UPDATE acc_academic_years SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }

    public function academicYearExists(string $label): bool
    {
        $stmt = $this->prepare("SELECT COUNT(*) AS total FROM acc_academic_years WHERE label = ? AND is_active = 1");
        $stmt->bind_param("s", $label);
        $this->execute($stmt);

        return ((int) ($this->fetchOne($stmt)['total'] ?? 0)) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD: Daftar Dokumen per Kriteria
    |--------------------------------------------------------------------------
    */

public function getDocumentsByCriteria(int $criteriaId): array
    {
        $stmt = $this->prepare("
            SELECT d.id, d.document_name,
                   (SELECT COUNT(*) FROM acc_document_units du WHERE du.document_id = d.id) AS unit_count
            FROM acc_documents d
            WHERE d.criteria_id = ? AND d.is_active = 1
            ORDER BY d.sort_order ASC
        ");
        $stmt->bind_param("i", $criteriaId);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function createDocument(int $criteriaId, string $documentName): int
    {
        $stmt = $this->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM acc_documents WHERE criteria_id = ?");
        $stmt->bind_param("i", $criteriaId);
        $this->execute($stmt);
        $nextOrder = (int) ($this->fetchOne($stmt)['next_order'] ?? 1);

        $stmt2 = $this->prepare("INSERT INTO acc_documents (criteria_id, document_name, sort_order, is_active) VALUES (?, ?, ?, 1)");
        $stmt2->bind_param("isi", $criteriaId, $documentName, $nextOrder);
        $this->execute($stmt2);

        return $this->insertId();
    }

    public function updateDocumentName(int $id, string $documentName): void
    {
        $stmt = $this->prepare("UPDATE acc_documents SET document_name = ? WHERE id = ?");
        $stmt->bind_param("si", $documentName, $id);
        $this->execute($stmt);
    }

    public function deleteDocument(int $id): void
    {
        $stmt = $this->prepare("UPDATE acc_documents SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | Kaitkan Tabel ke Kriteria
    |--------------------------------------------------------------------------
    */

    public function updateTableCriteria(int $tableId, ?int $criteriaId): void
    {
        $stmt = $this->prepare("UPDATE acc_tables SET criteria_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $criteriaId, $tableId);
        $this->execute($stmt);
    }
}