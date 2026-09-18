<?php

declare(strict_types=1);

class StandardRepository
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /* ==========================================================
     * MASTER DATA
     * ========================================================*/

    public function getTypes(): array
    {
        $sql = "
                SELECT
                    id,
                    code,
                    name
                FROM standard_types
                WHERE is_active = 1
                ORDER BY sort_order, name
                ";

        $result = $this->conn->query($sql);

        return $result
            ? $result->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    public function getCategories(?int $typeId = null): array
    {
        $sql = "
            SELECT
                id,
                type_id,
                code,
                name
            FROM standard_categories
            WHERE is_active = 1
        ";

        if ($typeId !== null) {
            $sql .= " AND type_id = ?";
        }

        $sql .= " ORDER BY sort_order ASC, name ASC";

        if ($typeId === null) {

            $result = $this->conn->query($sql);

            return $result
                ? $result->fetch_all(MYSQLI_ASSOC)
                : [];
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $typeId);
        $stmt->execute();

        return $stmt
            ->get_result()
            ->fetch_all(MYSQLI_ASSOC);
    }

    public function getStatuses(): array
    {
        $sql = "
            SELECT
                id,
                code,
                name,
                badge_color
            FROM standard_statuses
            WHERE is_active = 1
            ORDER BY sort_order ASC
        ";

        $result = $this->conn->query($sql);

        return $result
            ? $result->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    /* ==========================================================
     * TABLE
     * ========================================================*/

    public function getStandards(array $filter = []): array
    {
        $sql = "
            SELECT
                s.id, s.code, s.name,
                s.reference,
                s.version,
                s.revision,
                s.year,
                s.weight,
                s.publish_date,
                s.document_number,
                s.document_file,

                s.description,

                s.status,
                s.is_active,
                s.created_at, 
                s.updated_at,

                t.id   AS type_id, t.name AS type_name,
                c.id   AS category_id, c.name AS category_name,
                st.id           AS status_id,
                st.name         AS status_name,
                st.badge_color  AS badge_color,
                dt.id   AS document_type_id, dt.name AS document_type_name,
                (SELECT r.level_risiko FROM risk_register r
                    WHERE r.sumber_jenis = 'standar' AND r.sumber_id = s.id AND r.level_risiko IS NOT NULL
                    ORDER BY FIELD(r.level_risiko,'Ekstrem','Tinggi','Sedang','Rendah') ASC LIMIT 1) AS risk_level_tertinggi,
                (SELECT COUNT(*) FROM risk_register r WHERE r.sumber_jenis = 'standar' AND r.sumber_id = s.id) AS jumlah_risiko
            FROM standards s
            LEFT JOIN standard_types t ON t.id = s.type_id
            LEFT JOIN standard_categories c ON c.id = s.category_id
            LEFT JOIN standard_statuses st ON st.id = s.status_id
            LEFT JOIN standard_document_types dt ON dt.id = s.document_type_id
            WHERE s.is_active = 1
        ";

        $types  = "";
        $params = [];

        if (!empty($filter['type_id'])) {
            $sql .= " AND s.type_id = ? ";
            $types .= "i";
            $params[] = $filter['type_id'];
        }

        if (!empty($filter['category_id'])) {
            $sql .= " AND s.category_id = ? ";
            $types .= "i";
            $params[] = $filter['category_id'];
        }

        if (!empty($filter['status_id'])) {
            $sql .= " AND s.status_id = ? ";
            $types .= "i";
            $params[] = $filter['status_id'];
        }

        if (!empty($filter['keyword'])) {
            $sql .= "
                AND
                (
                    s.code LIKE ?
                    OR s.name LIKE ?
                    OR s.document_number LIKE ?
                    OR s.reference LIKE ?
                )
            ";

            $keyword = "%" . $filter['keyword'] . "%";
            $types .= "ssss";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= "
            ORDER BY
            s.sort_order ASC,
            s.code ASC
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException($this->conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStatements(int $standardId): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, statement_text, sort_order, owner_type, owner_unit_id
            FROM standard_statements
            WHERE standard_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->bind_param("i", $standardId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    /* ==========================================================
     * pernyataan standar
     * ========================================================*/
    public function replaceStatements(int $standardId, array $statements): void
    {
        $sort = 0;
        $keepIds = [];

        foreach ($statements as $item) {

            $text = trim((string) ($item['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $sort++;

            $ownerType = ($item['owner_type'] ?? 'prodi') === 'unit' ? 'unit' : 'prodi';
            $ownerUnitId = $ownerType === 'unit' && !empty($item['owner_unit_id']) ? (int) $item['owner_unit_id'] : null;
            $existingId = !empty($item['id']) ? (int) $item['id'] : 0;

            if ($existingId > 0) {

                /* Baris LAMA - UPDATE saja, ID tetap sama, hubungan ke Indikator TIDAK putus */

                $updateStmt = $this->conn->prepare("
                    UPDATE standard_statements
                    SET statement_text = ?, sort_order = ?, owner_type = ?, owner_unit_id = ?
                    WHERE id = ? AND standard_id = ?
                ");
                $updateStmt->bind_param("sisiii", $text, $sort, $ownerType, $ownerUnitId, $existingId, $standardId);
                $updateStmt->execute();

                $keepIds[] = $existingId;

            } else {

                /* Baris BARU - INSERT, dapat ID baru */

                $insertStmt = $this->conn->prepare("
                    INSERT INTO standard_statements (standard_id, statement_text, sort_order, owner_type, owner_unit_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $insertStmt->bind_param("isisi", $standardId, $text, $sort, $ownerType, $ownerUnitId);
                $insertStmt->execute();

                $keepIds[] = (int) $this->conn->insert_id;
            }
        }

        /* Hapus HANYA baris yang benar-benar dihapus user (tidak ada lagi di daftar keepIds) */

        if (!empty($keepIds)) {
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
            $types = str_repeat('i', count($keepIds));
            $params = $keepIds;
            array_unshift($params, $standardId);

            $deleteStmt = $this->conn->prepare("
                DELETE FROM standard_statements
                WHERE standard_id = ? AND id NOT IN ($placeholders)
            ");
            $deleteStmt->bind_param("i" . $types, ...$params);
            $deleteStmt->execute();
        } else {
            $deleteAll = $this->conn->prepare("DELETE FROM standard_statements WHERE standard_id = ?");
            $deleteAll->bind_param("i", $standardId);
            $deleteAll->execute();
        }
    }

    /* ==========================================================
     * Daftar Unit non-Prodi (utk dropdown Kepemilikan Pernyataan)
     * ========================================================*/

    public function getNonProdiUnits(): array
    {
        $stmt = $this->conn->prepare("SELECT id, code, name FROM units WHERE type <> 'Program Studi' ORDER BY name ASC");
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /* ==========================================================
     * JUMLAH TOTAL STANDAR DI CARD HERO
     * ========================================================*/

    public function countTotal(): int
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM standards
            WHERE is_active = 1
        ");

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return (int)($row['total'] ?? 0);
    }

    public function countByTypeCode(string $typeCode): int
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS total
            FROM standards s
            LEFT JOIN standard_types t ON t.id = s.type_id
            WHERE s.is_active = 1 AND t.code = ?
        ");

        $stmt->bind_param("s", $typeCode);

        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        return (int)($row['total'] ?? 0);
    }
    /* ==========================================================
     * FIND BY ID
     * ========================================================*/

    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                s.*,
                t.name  AS type_name,
                c.name  AS category_name,
                st.name AS status_name,
                st.badge_color AS badge_color,
                dt.name AS document_type_name
            FROM standards s
            LEFT JOIN standard_types t ON t.id = s.type_id
            LEFT JOIN standard_categories c ON c.id = s.category_id
            LEFT JOIN standard_statuses st ON st.id = s.status_id
            LEFT JOIN standard_document_types dt ON dt.id = s.document_type_id
            WHERE s.id = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        return $result ?: null;
    }

    /* ==========================================================
     * PILIH TIPE DOKUMEN
     * ========================================================*/

        public function getDocumentTypes(): array
    {
        $sql = "
            SELECT
                id,
                code,
                name,
                extension
            FROM standard_document_types
            WHERE is_active = 1
            ORDER BY name ASC
        ";

        $result = $this->conn->query($sql);

        return $result
            ? $result->fetch_all(MYSQLI_ASSOC)
            : [];
    }

    /* ==========================================================
     * CEK DUPLIKAT KODE
     * ========================================================*/

    public function existsCode(
        string $code,
        ?int $excludeId = null
    ): bool
    {
        if ($excludeId === null) {

            $sql = "
                SELECT id
                FROM standards
                WHERE code = ?
                LIMIT 1
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $code);

        } else {

            $sql = "
                SELECT id
                FROM standards
                WHERE code = ?
                AND id <> ?
                LIMIT 1
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "si",
                $code,
                $excludeId
            );
        }

        $stmt->execute();

        return $stmt
            ->get_result()
            ->num_rows > 0;
    }

    /* ==========================================================
     * CREATE
     * ========================================================*/

public function create(array $data): int
    {
        $sql = "
            INSERT INTO standards
            (
                code,
                name,

                reference,
                document_number,
                document_type_id,
                publish_date,

                type_id,
                category_id,
                status_id,

                category,

                version,
                revision,
                year,

                weight,
                sort_order,

                description,

                rasional,
                pihak_bertanggung_jawab,
                definisi_istilah,
                dokumen_terkait,
                referensi,

                perumusan_nama, perumusan_jabatan, perumusan_ttd, perumusan_tanggal,
                pemeriksaan_nama, pemeriksaan_jabatan, pemeriksaan_ttd, pemeriksaan_tanggal,
                persetujuan_nama, persetujuan_jabatan, persetujuan_ttd, persetujuan_tanggal,
                penetapan_nama, penetapan_jabatan, penetapan_ttd, penetapan_tanggal,
                pengendalian_nama, pengendalian_jabatan, pengendalian_ttd, pengendalian_tanggal,

                status,
                is_active,

                document_file,
                document_original_name,
                document_size,

                created_at,
                created_by
            )
            VALUES
            (
                ?, ?, ?,

                ?, ?, ?,

                ?, ?, ?,

                ?,

                ?, ?, ?,

                ?, ?,

                ?,

                ?, ?, ?, ?, ?,

                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,

                ?, ?,

                ?, ?, ?,

                NOW(),
                ?
            )
        ";

        $stmt = $this->conn->prepare($sql);

        $sortOrder = $data['sort_order'] ?? 0;
        $status = $data['status'] ?? 1;
        $createdBy = $data['created_by'] ?? ($_SESSION['user_id'] ?? 0);

        $stmt->bind_param(

            "ssssisiiissiidissssssssssssssssssssssssssiissii",

            $data['code'],
            $data['name'],

            $data['reference'],
            $data['document_number'],
            $data['document_type_id'],
            $data['publish_date'],

            $data['type_id'],
            $data['category_id'],
            $data['status_id'],

            $data['category'],

            $data['version'],
            $data['revision'],
            $data['year'],

            $data['weight'],
            $sortOrder,

            $data['description'],

            $data['rasional'],
            $data['pihak_bertanggung_jawab'],
            $data['definisi_istilah'],
            $data['dokumen_terkait'],
            $data['referensi'],

            $data['perumusan_nama'], $data['perumusan_jabatan'], $data['perumusan_ttd'], $data['perumusan_tanggal'],
            $data['pemeriksaan_nama'], $data['pemeriksaan_jabatan'], $data['pemeriksaan_ttd'], $data['pemeriksaan_tanggal'],
            $data['persetujuan_nama'], $data['persetujuan_jabatan'], $data['persetujuan_ttd'], $data['persetujuan_tanggal'],
            $data['penetapan_nama'], $data['penetapan_jabatan'], $data['penetapan_ttd'], $data['penetapan_tanggal'],
            $data['pengendalian_nama'], $data['pengendalian_jabatan'], $data['pengendalian_ttd'], $data['pengendalian_tanggal'],

            $status,
            $data['is_active'],

            $data['document_file'],
            $data['document_original_name'],
            $data['document_size'],

            $createdBy
        );

        $stmt->execute();

        return (int) $this->conn->insert_id;
    }

        /* ==========================================================
     * UPDATE
     * ========================================================*/

public function update(
        int $id,
        array $data
    ): bool
    {

        $sql = "

            UPDATE standards

            SET

                code=?,
                name=?,

                reference=?,
                document_number=?,
                document_type_id=?,
                publish_date=?,

                type_id=?,
                category_id=?,
                status_id=?,

                category=?,

                version=?,
                revision=?,
                year=?,

                weight=?,
                sort_order=?,

                description=?,

                rasional=?,
                pihak_bertanggung_jawab=?,
                definisi_istilah=?,
                dokumen_terkait=?,
                referensi=?,

                perumusan_nama=?, perumusan_jabatan=?, perumusan_ttd=?, perumusan_tanggal=?,
                pemeriksaan_nama=?, pemeriksaan_jabatan=?, pemeriksaan_ttd=?, pemeriksaan_tanggal=?,
                persetujuan_nama=?, persetujuan_jabatan=?, persetujuan_ttd=?, persetujuan_tanggal=?,
                penetapan_nama=?, penetapan_jabatan=?, penetapan_ttd=?, penetapan_tanggal=?,
                pengendalian_nama=?, pengendalian_jabatan=?, pengendalian_ttd=?, pengendalian_tanggal=?,

                status=?,
                is_active=?,

                document_file=?,
                document_original_name=?,
                document_size=?,

                updated_at=NOW(),
                updated_by=?

            WHERE id=?
        ";

            $stmt = $this->conn->prepare($sql);

            $sortOrder = $data['sort_order'] ?? 0;
            $status = $data['status'] ?? 1;
            $updatedBy = $data['updated_by'] ?? ($_SESSION['user_id'] ?? 0);

            $stmt->bind_param(

            "ssssisiiissiidissssssssssssssssssssssssssiissiii",

            $data['code'],
            $data['name'],

            $data['reference'],
            $data['document_number'],
            $data['document_type_id'],
            $data['publish_date'],

            $data['type_id'],
            $data['category_id'],
            $data['status_id'],

            $data['category'],

            $data['version'],
            $data['revision'],
            $data['year'],

            $data['weight'],
            $sortOrder,

            $data['description'],

            $data['rasional'],
            $data['pihak_bertanggung_jawab'],
            $data['definisi_istilah'],
            $data['dokumen_terkait'],
            $data['referensi'],

            $data['perumusan_nama'], $data['perumusan_jabatan'], $data['perumusan_ttd'], $data['perumusan_tanggal'],
            $data['pemeriksaan_nama'], $data['pemeriksaan_jabatan'], $data['pemeriksaan_ttd'], $data['pemeriksaan_tanggal'],
            $data['persetujuan_nama'], $data['persetujuan_jabatan'], $data['persetujuan_ttd'], $data['persetujuan_tanggal'],
            $data['penetapan_nama'], $data['penetapan_jabatan'], $data['penetapan_ttd'], $data['penetapan_tanggal'],
            $data['pengendalian_nama'], $data['pengendalian_jabatan'], $data['pengendalian_ttd'], $data['pengendalian_tanggal'],

            $status,
            $data['is_active'],

            $data['document_file'],
            $data['document_original_name'],
            $data['document_size'],

            $updatedBy,

            $id
        );

        return $stmt->execute();

    }

    /* ==========================================================
     * DELETE
     * ========================================================*/

    public function delete(int $id): bool
    {

        $stmt = $this->conn->prepare(

            "UPDATE standards
             SET is_active = 0, updated_at = NOW()
             WHERE id = ?"

        );

        $stmt->bind_param(

            "i",

            $id

        );

        return $stmt->execute();

    }

public function getVisibleStandardIdsForUnit(int $unitId): array
{
    $sql = "
        SELECT DISTINCT ai.standard_id
        FROM audit_indicators ai
        WHERE ai.status = 1
          AND (
              NOT EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id)
              OR EXISTS (SELECT 1 FROM audit_indicator_units aiu WHERE aiu.indicator_id = ai.id AND aiu.unit_id = ?)
          )
    ";

    $stmt = $this->conn->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException($this->conn->error);
    }

    $stmt->bind_param("i", $unitId);
    $stmt->execute();

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    return array_column($rows, 'standard_id');
}

}