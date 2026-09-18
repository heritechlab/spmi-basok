<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseRepository.php';

class SopRepository extends BaseRepository
{
    protected string $table = 'sop_documents';

    private function baseSelect(): string
    {
        return "
            SELECT
                sd.*,
                s.code AS standard_code, s.name AS standard_name
            FROM sop_documents sd
            LEFT JOIN standards s ON s.id = sd.standard_id
        ";
    }

    public function getAll(string $search = '', string $status = '', int $limit = 10, int $offset = 0): array
    {
        $sql = $this->baseSelect() . " WHERE 1 = 1 ";

        $types = '';
        $params = [];

        if ($status !== '') {
            $sql .= " AND sd.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (sd.document_number LIKE ? OR sd.title LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "ss";
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sql .= " ORDER BY sd.created_at DESC LIMIT ? OFFSET ? ";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $this->execute($stmt);

        return $this->fetchAll($stmt);
    }

    public function count(string $search = '', string $status = ''): int
    {
        $sql = "SELECT COUNT(*) AS total FROM sop_documents sd WHERE 1 = 1";

        $types = '';
        $params = [];

        if ($status !== '') {
            $sql .= " AND sd.status = ? ";
            $types .= "s";
            $params[] = $status;
        }

        if ($search !== '') {
            $sql .= " AND (sd.document_number LIKE ? OR sd.title LIKE ?) ";
            $keyword = "%{$search}%";
            $types .= "ss";
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $stmt = $this->prepare($sql);

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $this->execute($stmt);
        $row = $this->fetchOne($stmt);

        return (int)($row['total'] ?? 0);
    }

    public function getStatistics(): array
    {
        return [
            'total'    => $this->count(),
            'menunggu' => $this->count('', 'Menunggu Persetujuan'),
            'disahkan' => $this->count('', 'Disahkan'),
            'ditolak'  => $this->count('', 'Ditolak'),
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->prepare($this->baseSelect() . " WHERE sd.id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return $this->fetchOne($stmt) ?: null;
    }

    public function existsNumber(string $number, int $excludeId = 0): bool
    {
        $sql = "SELECT id FROM sop_documents WHERE document_number = ?";

        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
        }

        $stmt = $this->prepare($sql);

        if ($excludeId > 0) {
            $stmt->bind_param("si", $number, $excludeId);
        } else {
            $stmt->bind_param("s", $number);
        }

        $this->execute($stmt);

        return $this->fetchOne($stmt) !== null;
    }

public function create(array $data): int
    {
        $sql = "
            INSERT INTO sop_documents
                (document_number, title, standard_id, effective_date, revision, total_pages,
                 perumusan_nama, perumusan_jabatan, perumusan_ttd, perumusan_tanggal,
                 pemeriksaan_nama, pemeriksaan_jabatan, pemeriksaan_ttd, pemeriksaan_tanggal,
                 persetujuan_nama, persetujuan_jabatan, persetujuan_ttd, persetujuan_tanggal,
                 penetapan_nama, penetapan_jabatan, penetapan_ttd, penetapan_tanggal,
                 pengendalian_nama, pengendalian_jabatan, pengendalian_ttd, pengendalian_tanggal,
                 tujuan_prosedur, ruang_lingkup, definisi, prosedur, pihak_pelaksana,
                 bagan_alir_file, bagan_alir_original_name, catatan, status, created_by, created_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "ssisiisssssssssssssssssssssssssssssi",
            $data['document_number'],
            $data['title'],
            $data['standard_id'],
            $data['effective_date'],
            $data['revision'],
            $data['total_pages'],
            $data['perumusan_nama'], $data['perumusan_jabatan'], $data['perumusan_ttd'], $data['perumusan_tanggal'],
            $data['pemeriksaan_nama'], $data['pemeriksaan_jabatan'], $data['pemeriksaan_ttd'], $data['pemeriksaan_tanggal'],
            $data['persetujuan_nama'], $data['persetujuan_jabatan'], $data['persetujuan_ttd'], $data['persetujuan_tanggal'],
            $data['penetapan_nama'], $data['penetapan_jabatan'], $data['penetapan_ttd'], $data['penetapan_tanggal'],
            $data['pengendalian_nama'], $data['pengendalian_jabatan'], $data['pengendalian_ttd'], $data['pengendalian_tanggal'],
            $data['tujuan_prosedur'],
            $data['ruang_lingkup'],
            $data['definisi'],
            $data['prosedur'],
            $data['pihak_pelaksana'],
            $data['bagan_alir_file'],
            $data['bagan_alir_original_name'],
            $data['catatan'],
            $data['status'],
            $data['created_by']
        );

        $this->execute($stmt);

        return $this->insertId();
    }

public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE sop_documents
            SET document_number = ?, title = ?, standard_id = ?, effective_date = ?, revision = ?, total_pages = ?,
                perumusan_nama = ?, perumusan_jabatan = ?, perumusan_ttd = ?, perumusan_tanggal = ?,
                pemeriksaan_nama = ?, pemeriksaan_jabatan = ?, pemeriksaan_ttd = ?, pemeriksaan_tanggal = ?,
                persetujuan_nama = ?, persetujuan_jabatan = ?, persetujuan_ttd = ?, persetujuan_tanggal = ?,
                penetapan_nama = ?, penetapan_jabatan = ?, penetapan_ttd = ?, penetapan_tanggal = ?,
                pengendalian_nama = ?, pengendalian_jabatan = ?, pengendalian_ttd = ?, pengendalian_tanggal = ?,
                tujuan_prosedur = ?, ruang_lingkup = ?, definisi = ?, prosedur = ?, pihak_pelaksana = ?,
                catatan = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ";

        $stmt = $this->prepare($sql);

        $stmt->bind_param(
            "ssisiisssssssssssssssssssssssssssi",
            $data['document_number'],
            $data['title'],
            $data['standard_id'],
            $data['effective_date'],
            $data['revision'],
            $data['total_pages'],
            $data['perumusan_nama'], $data['perumusan_jabatan'], $data['perumusan_ttd'], $data['perumusan_tanggal'],
            $data['pemeriksaan_nama'], $data['pemeriksaan_jabatan'], $data['pemeriksaan_ttd'], $data['pemeriksaan_tanggal'],
            $data['persetujuan_nama'], $data['persetujuan_jabatan'], $data['persetujuan_ttd'], $data['persetujuan_tanggal'],
            $data['penetapan_nama'], $data['penetapan_jabatan'], $data['penetapan_ttd'], $data['penetapan_tanggal'],
            $data['pengendalian_nama'], $data['pengendalian_jabatan'], $data['pengendalian_ttd'], $data['pengendalian_tanggal'],
            $data['tujuan_prosedur'],
            $data['ruang_lingkup'],
            $data['definisi'],
            $data['prosedur'],
            $data['pihak_pelaksana'],
            $data['catatan'],
            $data['status'],
            $id
        );

        $this->execute($stmt);

        return true;
    }

    public function updateBaganAlir(int $id, string $file, string $originalName): bool
    {
        $stmt = $this->prepare("UPDATE sop_documents SET bagan_alir_file = ?, bagan_alir_original_name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $file, $originalName, $id);
        $this->execute($stmt);

        return true;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->prepare("DELETE FROM sop_documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $this->execute($stmt);

        return true;
    }
    public function approve(int $id, int $approvedBy): bool
    {
        $stmt = $this->prepare("
            UPDATE sop_documents
            SET status = 'Disahkan', approved_by = ?, approved_at = NOW(), rejection_note = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $approvedBy, $id);
        $this->execute($stmt);

        return true;
    }

    public function reject(int $id, int $approvedBy, string $note): bool
    {
        $stmt = $this->prepare("
            UPDATE sop_documents
            SET status = 'Ditolak', approved_by = ?, approved_at = NOW(), rejection_note = ?
            WHERE id = ?
        ");
        $stmt->bind_param("isi", $approvedBy, $note, $id);
        $this->execute($stmt);

        return true;
    }
}