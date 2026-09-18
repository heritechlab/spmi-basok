<?php

class UploadHelper
{
    /**
     * Root Upload
     */
    private string $uploadRoot;

    /**
     * Max Size (5 MB)
     */
    private int $maxSize = 5242880;

    /**
     * Allowed Extension
     */
    private array $allowedExtensions = [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx'
    ];

    /**
     * Allowed MIME
     */
    private array $allowedMimeTypes = [

        'application/pdf',

        'application/msword',

        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

        'application/vnd.ms-excel',

        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

    ];

    /**
     * Constructor
     */
    public function __construct(
            ?string $uploadRoot = null,
            ?array $allowedExtensions = null,
            ?array $allowedMimeTypes = null
        ) {
            $this->uploadRoot = $uploadRoot
                ?? dirname(__DIR__) . '/uploads/standards';

            if ($allowedExtensions !== null) {
                $this->allowedExtensions = $allowedExtensions;
            }

            if ($allowedMimeTypes !== null) {
                $this->allowedMimeTypes = $allowedMimeTypes;
            }
        }

    /**
     * Upload File
     */
    public function upload(array $file): array
    {

        if (
            empty($file) ||
            $file['error'] === UPLOAD_ERR_NO_FILE
        ) {
            throw new RuntimeException(
                'Dokumen belum dipilih.'
            );
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException(
                'Upload dokumen gagal.'
            );
        }

        $this->validateSize($file);

        $this->validateExtension($file);

        $this->validateMimeType($file);

        $year = date('Y');

        $directory =
            $this->uploadRoot .
            DIRECTORY_SEPARATOR .
            $year;

        if (!is_dir($directory)) {

            if (!mkdir($directory, 0777, true) && !is_dir($directory)) {

                throw new RuntimeException(
                    'Folder upload gagal dibuat.'
                );

            }

        }

        $extension = strtolower(

            pathinfo(

                $file['name'],

                PATHINFO_EXTENSION

            )

        );

        $newName =

            date('YmdHis') .

            '_' .

            bin2hex(random_bytes(4)) .

            '.' .

            $extension;

        $destination =

            $directory .

            DIRECTORY_SEPARATOR .

            $newName;

        if (

            !move_uploaded_file(

                $file['tmp_name'],

                $destination

            )

        ) {

            throw new RuntimeException(
                'Gagal menyimpan file.'
            );

        }

        $relativeRoot = str_replace(
            dirname(__DIR__) . '/',
            '',
            $this->uploadRoot
        );

        return [

            'document_file' =>

                $relativeRoot .

                '/' .

                $year .

                '/' .

                $newName,

            'document_original_name' =>

                $file['name'],

            'document_size' =>

                $file['size']

        ];

    }

    /**
     * Replace File
     */
    /**
 * Replace existing document with a new uploaded file.
 *
 * Flow:
 * 1. Upload the new file.
 * 2. If upload succeeds, delete the old file (if any).
 * 3. Return metadata for the new uploaded file.
 *
 * @param string|null $oldFile Relative path of the old document.
 * @param array $newFile Uploaded file ($_FILES['...']).
 *
 * @return array{
 *     document_file:string,
 *     document_original_name:string,
 *     document_size:int
 * }
 *
 * @throws RuntimeException
 */
public function replace(
    ?string $oldFile,
    array $newFile
): array {

    // Upload new document first.
    $result = $this->upload($newFile);

    // Delete old document only after upload succeeds.
    if (!empty($oldFile)) {

        try {

            $this->delete($oldFile);

        } catch (\Throwable $e) {

            /*
             * Upload has succeeded.
             * Do not rollback only because
             * old file deletion failed.
             *
             * Optional:
             * Write to application log here.
             */

        }

    }

    return $result;

}

    /**
     * Delete File
     */
    public function delete(?string $path): void
    {

        if (empty($path)) {

            return;

        }

        $file =

            dirname(__DIR__) .

            '/' .

            ltrim($path, '/');

        if (is_file($file)) {

            unlink($file);

        }

    }

    /**
     * Validate Size
     */
    private function validateSize(array $file): void
    {

        if ($file['size'] > $this->maxSize) {

            throw new RuntimeException(
                'Ukuran file maksimal 5 MB.'
            );

        }

    }

    /**
     * Validate Extension
     */
    private function validateExtension(array $file): void
    {

        $extension = strtolower(

            pathinfo(

                $file['name'],

                PATHINFO_EXTENSION

            )

        );

        if (

            !in_array(

                $extension,

                $this->allowedExtensions,

                true

            )

        ) {

            throw new RuntimeException(
                'Format dokumen tidak didukung.'
            );

        }

    }

    /**
     * Validate MIME
     */
    private function validateMimeType(array $file): void
    {

        $mime = mime_content_type(
            $file['tmp_name']
        );

        if (

            !in_array(

                $mime,

                $this->allowedMimeTypes,

                true

            )

        ) {

            throw new RuntimeException(
                'MIME Type dokumen tidak valid.'
            );

        }

    }

}