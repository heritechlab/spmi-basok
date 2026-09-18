<?php

declare(strict_types=1);

class StandardService
{
    private StandardRepository $repository;

    public function __construct(StandardRepository $repository)
    {
        $this->repository = $repository;
    }

    /* ==========================================================
     * MASTER
     * ========================================================*/

    public function getTypes(): array
    {
        return $this->repository->getTypes();
    }

    public function getCategories(?int $typeId = null): array
    {
        return $this->repository->getCategories($typeId);
    }

    public function getStatuses(): array
    {
        return $this->repository->getStatuses();
    }

    /* ==========================================================
     * TABLE
     * ========================================================*/

    public function getStandards(array $filter = []): array
    {
        return $this->repository->getStandards($filter);
    }

    public function findById(int $id): ?array
    {
        return $this->repository->findById($id);
    }
public function getAll(int $unitId = 0): array
    {
        $standards = $this->repository->getStandards();

        if ($unitId > 0) {

            $visibleIds = $this->repository->getVisibleStandardIdsForUnit($unitId);

            $standards = array_values(array_filter(
                $standards,
                fn($s) => in_array((int) $s['id'], $visibleIds)
            ));
        }

        return $standards;
    }

    public function getById(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    private function buildStatementsFromInput(array $data): array
    {
        $texts = $data['pernyataan_text'] ?? [];
        $ownerTypes = $data['pernyataan_owner_type'] ?? [];
        $ownerUnitIds = $data['pernyataan_owner_unit_id'] ?? [];
        $ids = $data['pernyataan_id'] ?? [];

        $result = [];

        foreach ($texts as $idx => $text) {
            $result[] = [
                'id'            => $ids[$idx] ?? null,
                'text'          => $text,
                'owner_type'    => $ownerTypes[$idx] ?? 'prodi',
                'owner_unit_id' => $ownerUnitIds[$idx] ?? null,
            ];
        }

        return $result;
    }
        /* ==========================================================
     * CREATE
     * ========================================================*/

    public function create(array $data, array $files = []): int
    {
        $rawStatements = $this->buildStatementsFromInput($data);

        $data = $this->normalize($data);

        $data['_statements'] = $rawStatements;

        $this->validate($data);

        if ($this->repository->existsCode($data['code'])) {
            throw new InvalidArgumentException(
                'Kode standar sudah digunakan.'
            );
        }

$data['document_file'] = null;
        $data['document_original_name'] = null;
        $data['document_size'] = null;

        if (!empty($files['document']['name'])) {
            require_once __DIR__ . '/../../core/UploadHelper.php';
            $uploader = new UploadHelper();
            $uploaded = $uploader->upload($files['document']);

            $data['document_file'] = $uploaded['document_file'];
            $data['document_original_name'] = $uploaded['document_original_name'];
            $data['document_size'] = $uploaded['document_size'];
        }

        $this->uploadSignatures($data, $files, []);

    $newId = $this->repository->create($data);

        if (!empty($data['_statements'])) {
            $this->repository->replaceStatements($newId, $data['_statements']);
        }

        return $newId;
    }

    /* ==========================================================
     * UPDATE
     * ========================================================*/

    public function update(int $id, array $data, array $files = []): bool
    {
        $rawStatements = $this->buildStatementsFromInput($data);

        $data = $this->normalize($data);

        $this->validate($data);

        if ($this->repository->existsCode($data['code'], $id)) {
            throw new InvalidArgumentException(
                'Kode standar sudah digunakan.'
            );
        }

        $existing = $this->repository->findById($id);

        $data['document_file'] = $existing['document_file'] ?? null;
        $data['document_original_name'] = $existing['document_original_name'] ?? null;
        $data['document_size'] = $existing['document_size'] ?? null;

        if (!empty($files['document']['name'])) {
            require_once __DIR__ . '/../../core/UploadHelper.php';
            $uploader = new UploadHelper();
            $uploaded = $uploader->replace(
                $existing['document_file'] ?? null,
                $files['document']
            );

            $data['document_file'] = $uploaded['document_file'];
            $data['document_original_name'] = $uploaded['document_original_name'];
            $data['document_size'] = $uploaded['document_size'];
        }

        $this->uploadSignatures($data, $files, $existing);

    $this->repository->replaceStatements($id, $rawStatements);

        return $this->repository->update($id, $data);
    }

    /* ==========================================================
     * DELETE
     * ========================================================*/

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'ID standar tidak valid.'
            );
        }

        return $this->repository->delete($id);
    }

    public function getNonProdiUnits(): array
    {
        return $this->repository->getNonProdiUnits();
    }
    /* ==========================================================
     * pernytaan standar
     * ========================================================*/
    public function getStatements(int $standardId): array
    {
        return $this->repository->getStatements($standardId);
    }
    /* ==========================================================
     * JENIS DOKUMEN
     * ========================================================*/

        public function getDocumentTypes(): array
    {
        return $this->repository->getDocumentTypes();
    }
        /* ==========================================================
     * NORMALIZE DATA
     * ========================================================*/

private function normalize(array $data): array
    {
        return [

            'code'          => trim($data['code'] ?? ''),
            'name'          => trim($data['name'] ?? ''),
            'reference'     => trim($data['reference'] ?? ''),
            'document_number' => trim($data['document_number'] ?? ''),
            'publish_date' => !empty($data['publish_date']) ? $data['publish_date'] : null,

            'type_id'       => (int)($data['type_id'] ?? 0),
            'category_id'   => (int)($data['category_id'] ?? 0),
            'status_id'     => (int)($data['status_id'] ?? 0),

            'document_type_id' => (int)($data['document_type_id'] ?? 0),

            // Diambil dari kategori terpilih.
            // Untuk saat ini dikosongkan terlebih dahulu.
            // Nanti bisa diisi otomatis dari repository jika diperlukan.
            'category'      => trim($data['category'] ?? ''),

            'version'       => trim($data['version'] ?? ''),
            'revision'      => (int)($data['revision'] ?? 0),
            'year'          => (int)($data['year'] ?? date('Y')),

            'weight'        => (float)($data['weight'] ?? 1),

            'sort_order'    => (int)($data['sort_order'] ?? 1),

            'description'   => trim($data['description'] ?? ''),

            'rasional'                 => trim($data['rasional'] ?? ''),
            'pihak_bertanggung_jawab'  => trim($data['pihak_bertanggung_jawab'] ?? ''),
            'definisi_istilah'         => trim($data['definisi_istilah'] ?? ''),
            'dokumen_terkait'          => trim($data['dokumen_terkait'] ?? ''),
            'referensi'                => trim($data['referensi'] ?? ''),

            'perumusan_nama'       => trim($data['perumusan_nama'] ?? ''),
            'perumusan_jabatan'    => trim($data['perumusan_jabatan'] ?? ''),
            'pemeriksaan_nama'     => trim($data['pemeriksaan_nama'] ?? ''),
            'pemeriksaan_jabatan'  => trim($data['pemeriksaan_jabatan'] ?? ''),
            'persetujuan_nama'     => trim($data['persetujuan_nama'] ?? ''),
            'persetujuan_jabatan'  => trim($data['persetujuan_jabatan'] ?? ''),
            'penetapan_nama'       => trim($data['penetapan_nama'] ?? ''),
            'penetapan_jabatan'    => trim($data['penetapan_jabatan'] ?? ''),
            'perumusan_tanggal'    => !empty($data['perumusan_tanggal']) ? $data['perumusan_tanggal'] : null,
            'pemeriksaan_tanggal'  => !empty($data['pemeriksaan_tanggal']) ? $data['pemeriksaan_tanggal'] : null,
            'persetujuan_tanggal'  => !empty($data['persetujuan_tanggal']) ? $data['persetujuan_tanggal'] : null,
            'penetapan_tanggal'    => !empty($data['penetapan_tanggal']) ? $data['penetapan_tanggal'] : null,
            'pengendalian_nama'    => trim($data['pengendalian_nama'] ?? ''),
            'pengendalian_jabatan' => trim($data['pengendalian_jabatan'] ?? ''),
            'pengendalian_tanggal' => !empty($data['pengendalian_tanggal']) ? $data['pengendalian_tanggal'] : null,

            'status'        => (int)($data['status'] ?? 1),

            'is_active'     => 1,

            'created_by'    => $_SESSION['user_id'] ?? 0,

            'updated_by'    => $_SESSION['user_id'] ?? 0
        ];
    }
    private function uploadSignatures(array &$data, array $files, array $existing): void
    {
        require_once __DIR__ . '/../../core/UploadHelper.php';

        $roles = ['perumusan', 'pemeriksaan', 'persetujuan', 'penetapan', 'pengendalian'];

        foreach ($roles as $role) {

            $fieldKey = $role . '_ttd';

            if (!empty($files[$fieldKey]['name'])) {

                $uploader = new UploadHelper(
                    dirname(__DIR__, 2) . '/uploads/signatures',
                    ['jpg', 'jpeg', 'png'],
                    ['image/jpeg', 'image/png']
                );

                $uploaded = $uploader->upload($files[$fieldKey]);
                $data[$fieldKey] = $uploaded['document_file'];

            } else {

                $data[$fieldKey] = $existing[$fieldKey] ?? null;

            }
        }
    }

    /* ==========================================================
     * VALIDATION
     * ========================================================*/

    private function validate(array $data): void
    {
        if ($data['code'] === '') {
            throw new InvalidArgumentException(
                'Kode standar wajib diisi.'
            );
        }

        if ($data['name'] === '') {
            throw new InvalidArgumentException(
                'Nama standar wajib diisi.'
            );
        }

        if ($data['document_number'] === '') {
            throw new InvalidArgumentException(
                'Nomor dokumen wajib diisi.'
            );
        }

        if ($data['type_id'] <= 0) {
            throw new InvalidArgumentException(
                'Jenis standar wajib dipilih.'
            );
        }

        if ($data['category_id'] <= 0) {
            throw new InvalidArgumentException(
                'Kategori standar wajib dipilih.'
            );
        }

        if ($data['status_id'] <= 0) {
            throw new InvalidArgumentException(
                'Status standar wajib dipilih.'
            );
        }

        if ($data['year'] < 2000) {
            throw new InvalidArgumentException(
                'Tahun tidak valid.'
            );
        }

        if ($data['weight'] < 0) {
            throw new InvalidArgumentException(
                'Bobot tidak boleh negatif.'
            );
        }

        if ($data['document_type_id'] <= 0) {
            throw new InvalidArgumentException(
                'Jenis dokumen wajib dipilih.'
            );
        }
    }

}