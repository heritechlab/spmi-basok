<?php

declare(strict_types=1);

/**
 * ==========================================================
 * SIQUA
 * Master Standar API V2
 * File : master/standards/api.php
 * ==========================================================
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/../../core/Auth.php';

/**
 * ==========================================================
 * SERVICE
 * ==========================================================
 */
$repository = new StandardRepository($conn);
$service = new StandardService($repository);

/**
 * ==========================================================
 * JSON RESPONSE
 * ==========================================================
 */
function jsonResponse(
    bool $success,
    string $message = '',
    $data = null,
    int $statusCode = 200
): void {

    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ],
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/**
 * ==========================================================
 * REQUEST
 * ==========================================================
 */
$action = strtolower(
    trim($_GET['action'] ?? 'list')
);

$requestMethod = strtoupper(
    $_SERVER['REQUEST_METHOD']
);

/**
 * ==========================================================
 * ROUTER
 * ==========================================================
 */
try {

    switch ($action) {

        /**
         * ==================================================
         * LIST
         * ==================================================
         */
case 'list':

            $unitId = Auth::isAuditee() ? (int) ($_SESSION['unit_id'] ?? 0) : 0;

            $rows = $service->getAll($unitId);

            jsonResponse(
                true,
                'Data berhasil diambil.',
                $rows
            );

            break;

        /**
         * ==================================================
         * GET
         * ==================================================
         */
        case 'get':

            $id = (int) ($_GET['id'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException(
                    'ID tidak valid.'
                );
            }

            $row = $service->getById($id);

            if (!$row) {
                throw new RuntimeException(
                    'Data tidak ditemukan.'
                );
            }

            jsonResponse(
                true,
                'Data berhasil diambil.',
                $row
            );

            break;

        /**
         * ==================================================
         * CREATE
         * ==================================================
         */
            case 'create':

            if (!Auth::canManage()) {
                throw new RuntimeException('Anda tidak memiliki akses untuk menambah data.');
            }

            if ($requestMethod !== 'POST') {
                throw new RuntimeException(
                    'Method harus POST.'
                );
            }

            $newId = $service->create(
                $_POST,
                $_FILES
            );

            jsonResponse(
                true,
                'Data berhasil disimpan.',
                [
                    'id' => $newId
                ]
            );

            break;
                    /**
         * ==================================================
         * UPDATE
         * ==================================================
         */
            case 'update':

            if (!Auth::canManage()) {
                throw new RuntimeException('Anda tidak memiliki akses untuk mengubah data.');
            }

            if ($requestMethod !== 'POST') {
                throw new RuntimeException(
                    'Method harus POST.'
                );
            }

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException(
                    'ID tidak valid.'
                );
            }

            $result = $service->update(
                $id,
                $_POST,
                $_FILES
            );

            jsonResponse(
                true,
                'Data berhasil diperbarui.',
                [
                    'updated' => $result
                ]
            );

            break;

                                /**
         * ==================================================
         * type
         * ==================================================
         */


            case 'types':

            $rows = $service->getTypes();

            jsonResponse(true, 'Data berhasil diambil.', $rows);

            break;
                 /**
         * ==================================================
         *KATEGORI
         * ==================================================
         */

            case 'categories':

                $typeId = !empty($_GET['type_id']) ? (int) $_GET['type_id'] : null;

                $rows = $service->getCategories($typeId);

                jsonResponse(true, 'Data berhasil diambil.', $rows);

                break;

                
                
                     /**
         * ==================================================
         * Pernytaan standar
         * ==================================================
         */
        case 'statements':

            $standardId = (int) ($_GET['standard_id'] ?? 0);

            $rows = $service->getStatements($standardId);

            jsonResponse(true, 'Data berhasil diambil.', $rows);

            break;
        

        case 'non_prodi_units':

            $rows = $service->getNonProdiUnits();

            jsonResponse(true, 'Data berhasil diambil.', $rows);

            break;
                     /**
         * ==================================================
         * STATUS
         * ==================================================
         */

            case 'statuses':

                $rows = $service->getStatuses();

                jsonResponse(true, 'Data berhasil diambil.', $rows);

                break;

                     /**
         * ==================================================
         * DOKUMEN
         * ==================================================
         */

            case 'document_types':

                $rows = $service->getDocumentTypes();

                jsonResponse(true, 'Data berhasil diambil.', $rows);

                break;
            /**
         * ==================================================
         * DELETE
         * ==================================================
         */
            case 'delete':

            if (!Auth::canManage()) {
                throw new RuntimeException('Anda tidak memiliki akses untuk menghapus data.');
            }

            if ($requestMethod !== 'POST') {
                throw new RuntimeException(
                    'Method harus POST.'
                );
            }

            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new InvalidArgumentException(
                    'ID tidak valid.'
                );
            }

            $result = $service->delete($id);

            jsonResponse(
                true,
                'Data berhasil dihapus.',
                [
                    'deleted' => $result
                ]
            );

            break;

        /**
         * ==================================================
         * DEFAULT
         * ==================================================
         */
        default:

            throw new RuntimeException(
                'Action tidak dikenali.'
            );
                }

} catch (InvalidArgumentException $e) {

    jsonResponse(
        false,
        $e->getMessage(),
        null,
        400
    );

} catch (RuntimeException $e) {

    jsonResponse(
        false,
        $e->getMessage(),
        null,
        500
    );

} catch (Throwable $e) {

    jsonResponse(
        false,
        'Terjadi kesalahan pada server.',
        [
            'error' => $e->getMessage()
        ],
        500
    );

}