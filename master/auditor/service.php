<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class AuditorService extends BaseService
{
    public function __construct(AuditorRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): AuditorRepository
    {
        /** @var AuditorRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', int $status = 1, int $limit = 10, int $offset = 0): array
    {
        return $this->success(
            $this->repository()->getAll($search, $status, $limit, $offset)
        );
    }

    public function count(string $search = '', int $status = 1): array
    {
        return $this->success(
            $this->repository()->count($search, $status)
        );
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Data auditor tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data, bool $isEdit): array
    {
        return [
            'full_name' => trim($data['full_name'] ?? ''),
            'nidn_nip'  => trim($data['nidn_nip'] ?? ''),
            'username'  => trim($data['username'] ?? ''),
            'password'  => !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null,
            'email'     => trim($data['email'] ?? ''),
            'phone'     => trim($data['phone'] ?? ''),
            'status'    => (int)($data['status'] ?? 1),
            'certificate_number' => trim($data['certificate_number'] ?? ''),
        ];
    }

    private function validate(array $data, bool $isEdit): void
    {
        if ($data['full_name'] === '') {
            throw new InvalidArgumentException('Nama lengkap wajib diisi.');
        }

        if ($data['username'] === '') {
            throw new InvalidArgumentException('Username wajib diisi.');
        }

        if (!$isEdit && empty($data['password'])) {
            throw new InvalidArgumentException('Password wajib diisi.');
        }
    }

public function create(array $input, array $files = []): array
    {
        try {

            $data = $this->normalize($input, false);

            $this->validate($data, false);

            if ($this->repository()->existsUsername($data['username'])) {
                throw new InvalidArgumentException('Username sudah digunakan.');
            }

            $data['certificate_file'] = null;
            $data['certificate_original_name'] = null;
            $data['certificate_size'] = null;

            if (!empty($files['certificate']['name'])) {
                require_once __DIR__ . '/../../core/UploadHelper.php';
                $uploader = new UploadHelper();
                $uploaded = $uploader->upload($files['certificate']);

                $data['certificate_file'] = $uploaded['document_file'];
                $data['certificate_original_name'] = $uploaded['document_original_name'];
                $data['certificate_size'] = $uploaded['document_size'];
            }

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Auditor berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input, array $files = []): array
    {
        try {

            $data = $this->normalize($input, true);

            $this->validate($data, true);

            if ($this->repository()->existsUsername($data['username'], $id)) {
                throw new InvalidArgumentException('Username sudah digunakan.');
            }

            $existing = $this->repository()->findById($id);

            $data['certificate_file'] = $existing['certificate_file'] ?? null;
            $data['certificate_original_name'] = $existing['certificate_original_name'] ?? null;
            $data['certificate_size'] = $existing['certificate_size'] ?? null;

            if (!empty($files['certificate']['name'])) {
                require_once __DIR__ . '/../../core/UploadHelper.php';
                $uploader = new UploadHelper();
                $uploaded = $uploader->replace($existing['certificate_file'] ?? null, $files['certificate']);

                $data['certificate_file'] = $uploaded['document_file'];
                $data['certificate_original_name'] = $uploaded['document_original_name'];
                $data['certificate_size'] = $uploaded['document_size'];
            }

            $this->repository()->update($id, $data);

            return $this->success(null, 'Data auditor berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        if ($id <= 0) {
            return $this->error('ID auditor tidak valid.');
        }

        $this->repository()->delete($id);

        return $this->success([], 'Auditor berhasil dinonaktifkan.');
    }
}