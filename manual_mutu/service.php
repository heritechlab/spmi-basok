<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class ManualMutuService extends BaseService
{
    public function __construct(ManualMutuRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): ManualMutuRepository
    {
        /** @var ManualMutuRepository */
        return parent::repository();
    }

    public function getByCategory(string $category): array
    {
        return $this->success($this->repository()->getByCategory($category));
    }

    public function upload(array $input, array $file, int $uploadedBy): array
    {
        try {

            $category = trim($input['category'] ?? '');

            if (!in_array($category, ['manual_ppepp', 'manual_lainnya'], true)) {
                throw new InvalidArgumentException('Kategori tidak valid.');
            }

            $title = trim($input['title'] ?? '');

            if ($title === '') {
                throw new InvalidArgumentException('Judul Dokumen wajib diisi.');
            }

            if (empty($file['name'])) {
                throw new InvalidArgumentException('File dokumen wajib diunggah.');
            }

            require_once __DIR__ . '/../core/UploadHelper.php';

            $uploader = new UploadHelper(
                dirname(__DIR__) . '/uploads/manual_mutu',
                ['pdf'],
                ['application/pdf']
            );

            $result = $uploader->upload($file);

            $data = [
                'category'            => $category,
                'title'               => $title,
                'description'         => trim($input['description'] ?? '') ?: null,
                'nomor_dokumen'       => trim($input['nomor_dokumen'] ?? '') ?: null,
                'tanggal_berlaku'     => !empty($input['tanggal_berlaku']) ? $input['tanggal_berlaku'] : null,
                'file_path'           => $result['document_file'],
                'file_original_name'  => $result['document_original_name'],
                'uploaded_by'         => $uploadedBy,
            ];

            $this->repository()->create($data);

            return $this->success(null, 'Dokumen berhasil diunggah.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        try {

            $this->repository()->delete($id);

            return $this->success(null, 'Dokumen berhasil dihapus.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}