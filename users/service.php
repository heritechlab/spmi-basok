<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): UserRepository
    {
        /** @var UserRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', int $roleId = 0, int $status = -1, int $limit = 10, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($search, $roleId, $status, $limit, $offset));
    }

    public function count(string $search = '', int $roleId = 0, int $status = -1): array
    {
        return $this->success($this->repository()->count($search, $roleId, $status));
    }

    public function getStatistics(): array
    {
        return $this->success($this->repository()->getStatistics());
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Pengguna tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'role_id'   => (int)($data['role_id'] ?? 0),
            'unit_id'   => !empty($data['unit_id']) ? (int) $data['unit_id'] : null,
            'full_name' => trim($data['full_name'] ?? ''),
            'username'  => trim($data['username'] ?? ''),
            'email'     => trim($data['email'] ?? ''),
            'phone'     => trim($data['phone'] ?? ''),
            'status'    => (int)($data['status'] ?? 1),
        ];
    }

    private function validate(array $data): void
    {
        if (!in_array($data['role_id'], [1, 2, 3, 4, 5], true)) {
            throw new InvalidArgumentException('Peran (Role) wajib dipilih.');
        }

        if ($data['full_name'] === '') {
            throw new InvalidArgumentException('Nama lengkap wajib diisi.');
        }

        if ($data['username'] === '') {
            throw new InvalidArgumentException('Username wajib diisi.');
        }

        if ($data['role_id'] === 4 && empty($data['unit_id'])) {
            throw new InvalidArgumentException('Untuk role Auditee, Unit Kerja wajib dipilih.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsUsername($data['username'])) {
                throw new InvalidArgumentException('Username sudah digunakan.');
            }

            $password = trim($input['password'] ?? '');

            if (strlen($password) < 6) {
                throw new InvalidArgumentException('Password wajib diisi, minimal 6 karakter.');
            }

            $data['password'] = password_hash($password, PASSWORD_BCRYPT);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'Pengguna berhasil ditambahkan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsUsername($data['username'], $id)) {
                throw new InvalidArgumentException('Username sudah digunakan.');
            }

            $this->repository()->update($id, $data);

            $newPassword = trim($input['password'] ?? '');

            if ($newPassword !== '') {

                if (strlen($newPassword) < 6) {
                    throw new InvalidArgumentException('Password baru minimal 6 karakter.');
                }

                $this->repository()->updatePassword($id, password_hash($newPassword, PASSWORD_BCRYPT));
            }

            return $this->success(null, 'Pengguna berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function toggleStatus(int $id, int $status): array
    {
        $this->repository()->toggleStatus($id, $status);

        return $this->success(null, $status === 1 ? 'Pengguna diaktifkan kembali.' : 'Pengguna dinonaktifkan.');
    }
}