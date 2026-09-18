<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AkreditasiProdiService extends BaseService
{
    private array $kriteriaOptions = [
        'Kriteria 1. Visi, Misi, Tujuan, dan Strategi',
        'Kriteria 2. Kurikulum',
        'Kriteria 3. Penilaian',
        'Kriteria 4. Mahasiswa',
        'Kriteria 5. Dosen, Tenaga Kependidikan, Penelitian dan PKM',
        'Kriteria 6. Sarana, Prasarana Pendidikan, dan Keuangan',
        'Kriteria 7. Penjaminan Mutu',
        'Kriteria 8. Tata Kelola dan Administrasi',
    ];

    public function __construct(AkreditasiProdiRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AkreditasiProdiRepository
    {
        /** @var AkreditasiProdiRepository */
        return parent::repository();
    }

    public function getKriteriaOptions(): array
    {
        return $this->kriteriaOptions;
    }

    public function getAll(string $kriteria = '', int $unitId = 0, string $academicYear = '', int $limit = 20, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($kriteria, $unitId, $academicYear, $limit, $offset));
    }

    public function count(string $kriteria = '', int $unitId = 0, string $academicYear = ''): array
    {
        return $this->success($this->repository()->count($kriteria, $unitId, $academicYear));
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
            $unitId = (int) ($input['unit_id'] ?? 0);

            if (!in_array($kriteria, $this->kriteriaOptions, true)) {
                throw new InvalidArgumentException('Kriteria wajib dipilih.');
            }

            if ($documentName === '') {
                throw new InvalidArgumentException('Nama Dokumen wajib diisi.');
            }

            if ($unitId <= 0) {
                throw new InvalidArgumentException('Program Studi wajib dipilih.');
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

                $uploadRoot = dirname(__DIR__) . '/uploads/akreditasi_prodi';

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
                'unit_id'             => $unitId,
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