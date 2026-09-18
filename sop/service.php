<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/repository.php';

class SopService extends BaseService
{
    public function __construct(SopRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function repository(): SopRepository
    {
        /** @var SopRepository */
        return parent::repository();
    }

    public function getAll(string $search = '', string $status = '', int $limit = 10, int $offset = 0): array
    {
        return $this->success($this->repository()->getAll($search, $status, $limit, $offset));
    }

    public function count(string $search = '', string $status = ''): array
    {
        return $this->success($this->repository()->count($search, $status));
    }

    public function getStatistics(): array
    {
        return $this->success($this->repository()->getStatistics());
    }

    public function getById(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Dokumen SOP tidak ditemukan.');
        }

        return $this->success($data);
    }

    private function normalize(array $data): array
    {
        return [
            'document_number'    => trim($data['document_number'] ?? ''),
            'title'               => trim($data['title'] ?? ''),
            'standard_id'         => !empty($data['standard_id']) ? (int) $data['standard_id'] : null,
            'effective_date'      => !empty($data['effective_date']) ? $data['effective_date'] . '-01' : null,
            'revision'            => (int)($data['revision'] ?? 0),
            'total_pages'          => (int)($data['total_pages'] ?? 1),
            'perumusan_tanggal'    => !empty($data['perumusan_tanggal']) ? $data['perumusan_tanggal'] : null,
            'pemeriksaan_tanggal'  => !empty($data['pemeriksaan_tanggal']) ? $data['pemeriksaan_tanggal'] : null,
            'persetujuan_tanggal'  => !empty($data['persetujuan_tanggal']) ? $data['persetujuan_tanggal'] : null,
            'penetapan_tanggal'    => !empty($data['penetapan_tanggal']) ? $data['penetapan_tanggal'] : null,
            'pengendalian_tanggal' => !empty($data['pengendalian_tanggal']) ? $data['pengendalian_tanggal'] : null,
            'perumusan_nama'       => trim($data['perumusan_nama'] ?? ''),
            'perumusan_jabatan'    => trim($data['perumusan_jabatan'] ?? ''),
            'pemeriksaan_nama'     => trim($data['pemeriksaan_nama'] ?? ''),
            'pemeriksaan_jabatan'  => trim($data['pemeriksaan_jabatan'] ?? ''),
            'persetujuan_nama'     => trim($data['persetujuan_nama'] ?? ''),
            'persetujuan_jabatan'  => trim($data['persetujuan_jabatan'] ?? ''),
            'penetapan_nama'       => trim($data['penetapan_nama'] ?? ''),
            'penetapan_jabatan'    => trim($data['penetapan_jabatan'] ?? ''),
            'pengendalian_nama'    => trim($data['pengendalian_nama'] ?? ''),
            'pengendalian_jabatan' => trim($data['pengendalian_jabatan'] ?? ''),
            'tujuan_prosedur'     => trim($data['tujuan_prosedur'] ?? ''),
            'ruang_lingkup'       => trim($data['ruang_lingkup'] ?? ''),
            'definisi'            => trim($data['definisi'] ?? ''),
            'prosedur'            => trim($data['prosedur'] ?? ''),
            'pihak_pelaksana'     => trim($data['pihak_pelaksana'] ?? ''),
            'catatan'             => trim($data['catatan'] ?? ''),
        ];
    }

    private function uploadSignatures(array &$data, array $files, array $existing): void
    {
        require_once __DIR__ . '/../core/UploadHelper.php';

        $roles = ['perumusan', 'pemeriksaan', 'persetujuan', 'penetapan', 'pengendalian'];

        foreach ($roles as $role) {

            $fieldKey = $role . '_ttd';

            if (!empty($files[$fieldKey]['name'])) {

                $uploader = new UploadHelper(
                    dirname(__DIR__) . '/uploads/signatures',
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

    private function validate(array $data): void
    {
        if ($data['document_number'] === '') {
            throw new InvalidArgumentException('No. Dokumen wajib diisi.');
        }

        if ($data['title'] === '') {
            throw new InvalidArgumentException('Judul SOP wajib diisi.');
        }
    }

    public function create(array $input): array
    {
        try {

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsNumber($data['document_number'])) {
                throw new InvalidArgumentException('No. Dokumen sudah digunakan.');
            }

            $data['bagan_alir_file'] = null;
            $data['bagan_alir_original_name'] = null;
            $data['status'] = 'Menunggu Persetujuan';
            $data['created_by'] = $_SESSION['user_id'] ?? 0;

            $this->uploadSignatures($data, $_FILES ?? [], []);

            $id = $this->repository()->create($data);

            return $this->success(['id' => $id], 'SOP berhasil diajukan, menunggu persetujuan Admin/Ka. LPM.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(int $id, array $input): array
    {
        try {

            $existing = $this->repository()->findById($id);

            if (!$existing) {
                throw new InvalidArgumentException('Dokumen SOP tidak ditemukan.');
            }

            if ($existing['status'] === 'Disahkan') {
                throw new InvalidArgumentException('SOP yang sudah Disahkan tidak dapat diubah lagi.');
            }

            $data = $this->normalize($input);

            $this->validate($data);

            if ($this->repository()->existsNumber($data['document_number'], $id)) {
                throw new InvalidArgumentException('No. Dokumen sudah digunakan.');
            }

            // Kalau sebelumnya Ditolak lalu direvisi, kembalikan ke antrean persetujuan.
        $data['status'] = $existing['status'] === 'Ditolak' ? 'Menunggu Persetujuan' : $existing['status'];

            $this->uploadSignatures($data, $_FILES ?? [], $existing);

            $this->repository()->update($id, $data);

            return $this->success(null, 'Dokumen SOP berhasil diperbarui.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        $this->repository()->delete($id);

        return $this->success(null, 'Dokumen SOP berhasil dihapus.');
    }
    public function approve(int $id): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Dokumen SOP tidak ditemukan.');
        }

        $this->repository()->approve($id, $_SESSION['user_id'] ?? 0);

        return $this->success(null, 'SOP berhasil Disahkan.');
    }

    public function reject(int $id, string $note): array
    {
        $data = $this->repository()->findById($id);

        if (!$data) {
            return $this->error('Dokumen SOP tidak ditemukan.');
        }

        $this->repository()->reject($id, $_SESSION['user_id'] ?? 0, $note);

        return $this->success(null, 'SOP dikembalikan untuk direvisi.');
    }

public function uploadBaganAlir(int $id, array $file): array
    {
        try {

            require_once __DIR__ . '/../core/UploadHelper.php';

            $uploadRoot = dirname(__DIR__) . '/uploads/sop';

            $uploader = new UploadHelper($uploadRoot, ['jpg', 'jpeg', 'png'], ['image/jpeg', 'image/png']);
            $result = $uploader->upload($file);

            $this->repository()->updateBaganAlir($id, $result['document_file'], $result['document_original_name']);

            return $this->success(null, 'Bagan Alir berhasil diunggah.');

        } catch (Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}