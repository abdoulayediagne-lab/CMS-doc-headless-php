<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\MediaRepository;

class PostMediaController extends AbstractController {
    private const MAX_FILE_SIZE = 5_242_880; // 5MB

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin', 'editor', 'author']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            return new Response(
                json_encode(['error' => 'file is required (multipart/form-data)']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $uploadedFile = $_FILES['file'];
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return new Response(
                json_encode(['error' => 'file upload failed']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $tmpPath = (string) ($uploadedFile['tmp_name'] ?? '');
        $originalName = (string) ($uploadedFile['name'] ?? '');
        $fileSize = (int) ($uploadedFile['size'] ?? 0);

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            return new Response(
                json_encode(['error' => 'invalid uploaded file']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if ($fileSize <= 0 || $fileSize > self::MAX_FILE_SIZE) {
            return new Response(
                json_encode(['error' => 'file size must be between 1 byte and 5 MB']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpPath);
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return new Response(
                json_encode(['error' => 'unsupported mime type']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $documentId = isset($_POST['document_id']) && $_POST['document_id'] !== '' ? (int) $_POST['document_id'] : null;
        if ($documentId !== null) {
            if ($documentId <= 0) {
                return new Response(
                    json_encode(['error' => 'invalid document_id']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $documentRepository = new DocumentRepository();
            if ($documentRepository->findById($documentId) === null) {
                return new Response(
                    json_encode(['error' => 'document not found']),
                    404,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $altText = isset($_POST['alt_text']) ? trim((string) $_POST['alt_text']) : null;
        if ($altText === '') {
            $altText = null;
        }
        if ($altText !== null && mb_strlen($altText) > 255) {
            return new Response(
                json_encode(['error' => 'alt_text must be <= 255 chars']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $storageDirectory = dirname(__DIR__, 3) . '/uploads/media';
        if (!is_dir($storageDirectory) && !mkdir($storageDirectory, 0775, true) && !is_dir($storageDirectory)) {
            return new Response(
                json_encode(['error' => 'unable to create media directory']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $safeBaseName = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename($originalName));
        $safeBaseName = $safeBaseName === '' ? 'media-file' : $safeBaseName;
        $storedFilename = uniqid('media_', true) . '_' . $safeBaseName;
        $destinationPath = $storageDirectory . '/' . $storedFilename;

        if (!move_uploaded_file($tmpPath, $destinationPath)) {
            return new Response(
                json_encode(['error' => 'unable to persist uploaded file']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $relativePath = '/uploads/media/' . $storedFilename;

        $mediaRepository = new MediaRepository();
        $media = $mediaRepository->create([
            'document_id' => $documentId,
            'uploaded_by' => $authorizedUser->getId(),
            'filename' => $safeBaseName,
            'alt_text' => $altText,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'path' => $relativePath,
        ]);

        if ($media === null) {
            @unlink($destinationPath);
            return new Response(
                json_encode(['error' => 'unable to create media record']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $authorizedUser->getId(),
            'upload',
            'media',
            $media->getId(),
            null,
            [
                'filename' => $media->filename,
                'mime_type' => $media->mime_type,
                'document_id' => $media->document_id,
            ]
        );

        return new Response(
            json_encode([
                'id' => $media->id,
                'document_id' => $media->document_id,
                'uploaded_by' => $media->uploaded_by,
                'filename' => $media->filename,
                'alt_text' => $media->alt_text,
                'mime_type' => $media->mime_type,
                'file_size' => $media->file_size,
                'path' => $media->path,
                'created_at' => $media->created_at,
            ]),
            201,
            ['Content-Type' => 'application/json']
        );
    }
}

?>