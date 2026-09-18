<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class BuktiService extends BaseService
{
    private array $categories = [
        'Kurikulum', 'Panduan', 'RPS', 'Absensi', 'Nilai', 'SK Dosen Pengampu', 'Lainnya',
    ];

    public function __construct(BuktiRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): BuktiRepository
    {
        /** @var BuktiRepository */
        return parent::repository();
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

public function getAll(string $category = '', int $unitId = 0, string $semester = '', string $academicYear = '', string $fileType = '', int $limit = 20, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($category, $unitId, $semester, $academicYear, $fileType, $limit, $offset));
    }

    public function count(string $category = '', int $unitId = 0, string $semester = '', string $academicYear = '', string $fileType = ''): array
    {
        return $this->success($this->repository()->count($category, $unitId, $semester, $academicYear, $fileType));
    }

public function upload(array $input, array $file): array
    {
        try {

            $category = trim($input['category'] ?? '');
            $description = trim($input['description'] ?? '');
            $unitId = (int) ($input['unit_id'] ?? 0);
            $semester = trim($input['semester'] ?? '');
            $academicYear = trim($input['academic_year'] ?? '');

            if (!in_array($category, $this->categories, true)) {
                throw new InvalidArgumentException('Kategori tidak valid.');
            }

            $documentName = trim($input['document_name'] ?? '');

            if ($documentName === '') {
                throw new InvalidArgumentException('Nama Dokumen wajib diisi.');
            }

            if ($unitId <= 0) {
                throw new InvalidArgumentException('Unit Kerja wajib dipilih.');
            }

            if (!in_array($semester, ['Ganjil', 'Genap'], true)) {
                throw new InvalidArgumentException('Semester wajib dipilih.');
            }

            if ($academicYear === '') {
                throw new InvalidArgumentException('Tahun Akademik wajib diisi.');
            }

            if (empty($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
                throw new InvalidArgumentException('File belum dipilih.');
            }

            require_once __DIR__ . '/../core/UploadHelper.php';

            $uploadRoot = dirname(__DIR__) . '/uploads/bukti_pelaksanaan';

            $uploader = new UploadHelper($uploadRoot, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'], [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
            ]);

            $result = $uploader->upload($file);

            $id = $this->repository()->create([
                'category'            => $category,
                'unit_id'             => $unitId,
                'semester'            => $semester,
                'academic_year'       => $academicYear,
                'document_name'       => $documentName,
                'description'         => $description,
                'file_path'           => $result['document_file'],
                'file_original_name'  => $result['document_original_name'],
                'file_type'           => null,
                'uploaded_by'         => $_SESSION['user_id'] ?? 0,
            ]);

            return $this->success(['id' => $id], 'Bukti pelaksanaan berhasil diunggah.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Bukti pelaksanaan berhasil dihapus.');
    }
}