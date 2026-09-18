<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class SettingsService extends BaseService
{
    public function __construct(SettingsRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): SettingsRepository
    {
        /** @var SettingsRepository */
        return parent::repository();
    }

    public function getAll(): array
    {
        return $this->success($this->repository()->getAll());
    }

    public function updateGeneral(array $input): array
    {
        try {

            $appName = trim($input['app_name'] ?? '');
            $itemsPerPage = (int) ($input['items_per_page'] ?? 10);

            if ($appName === '') {
                throw new InvalidArgumentException('Nama Aplikasi wajib diisi.');
            }

            if ($itemsPerPage < 5 || $itemsPerPage > 100) {
                throw new InvalidArgumentException('Item per Halaman harus di antara 5 - 100.');
            }

            $this->repository()->updateBatch([
                'app_name' => $appName,
                'items_per_page' => (string) $itemsPerPage,
            ]);

            return $this->success(null, 'Pengaturan Umum berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateEmail(array $input): array
    {
        try {

            $host = trim($input['smtp_host'] ?? '');
            $port = trim($input['smtp_port'] ?? '587');
            $username = trim($input['smtp_username'] ?? '');
            $password = trim($input['smtp_password'] ?? '');
            $fromEmail = trim($input['smtp_from_email'] ?? '');
            $fromName = trim($input['smtp_from_name'] ?? 'SIQUA');

            $data = [
                'smtp_host' => $host,
                'smtp_port' => $port,
                'smtp_username' => $username,
                'smtp_from_email' => $fromEmail,
                'smtp_from_name' => $fromName,
            ];

            if ($password !== '') {
                $data['smtp_password'] = $password;
            }

            $this->repository()->updateBatch($data);

            return $this->success(null, 'Pengaturan Email berhasil disimpan.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}