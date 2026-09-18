<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AkreditasiInstitusiService extends BaseService
{
    private array $kriteriaOptions = [
        'Kriteria 1. Budaya Mutu',
        'Kriteria 2.1. Relevansi Pendidikan',
        'Kriteria 2.2. Relevansi Penelitian',
        'Kriteria 2.3. Relevansi PKM',
        'Kriteria 3. Akuntabilitas',
        'Kriteria 4. Diferensiasi Misi',
    ];

    public function __construct(AkreditasiInstitusiRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AkreditasiInstitusiRepository
    {
        /** @var AkreditasiInstitusiRepository */
        return parent::repository();
    }

    public function getKriteriaOptions(): array
    {
        return $this->kriteriaOptions;
    }

    public function getAll(string $kriteria = '', string $academicYear = '', int $limit = 20, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($kriteria, $academicYear, $limit, $offset));
    }

    public function count(string $kriteria = '', string $academicYear = ''): array
    {
        return $this->success($this->repository()->count($kriteria, $academicYear));
    }

    public function getStatistics(): array
    {
        return ['total' => $this->repository()->count()];
    }

    public function upload(array $input, array $file): array
    {
        try {

            $kriteria = trim($input['kriteria'] ?? '');
            $documentName = trim($input['document_name'] ?? '');
            $academicYear = trim($input['academic_year'] ?? '');

            if (!in_array($kriteria, $this->kriteriaOptions, true)) {
                throw new InvalidArgumentException('Kriteria wajib dipilih.');
            }

            if ($documentName === '') {
                throw new InvalidArgumentException('Nama Dokumen wajib diisi.');
            }

if ($academicYear === '') {
                throw new InvalidArgumentException('Tahun Akademik wajib diisi.');
            }

            $linkUrl = trim($input['link_url'] ?? '');
            $hasFile = !empty($file) && $file['error'] !== UPLOAD_ERR_NO_FILE;

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

                $uploadRoot = dirname(__DIR__) . '/uploads/akreditasi_institusi';

                $uploader = new UploadHelper($uploadRoot, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'], [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'image/jpeg',
                    'image/png',
                ]);

                $result = $uploader->upload($file);

                $filePath = $result['document_file'];
                $fileOriginalName = $result['document_original_name'];
            }

            $id = $this->repository()->create([
                'kriteria'            => $kriteria,
                'document_name'       => $documentName,
                'academic_year'       => $academicYear,
                'file_path'           => $filePath,
                'file_original_name'  => $fileOriginalName,
                'link_url'            => $linkUrl !== '' ? $linkUrl : null,
                'uploaded_by'         => $_SESSION['user_id'] ?? 0,
            ]);

            return $this->success(['id' => $id], 'Dokumen berhasil diunggah.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
}