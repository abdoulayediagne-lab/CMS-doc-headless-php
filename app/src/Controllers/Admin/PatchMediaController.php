<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\MediaRepository;

class PatchMediaController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin', 'editor', 'author']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid media id']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $mediaRepository = new MediaRepository();
        $media = $mediaRepository->findById($id);
        if ($media === null) {
            return new Response(
                json_encode(['error' => 'media not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        if ($authorizedUser->getRole() === 'author' && $media->uploaded_by !== $authorizedUser->getId()) {
            return new Response(
                json_encode(['error' => 'forbidden: you can only edit your own media']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $payload = json_decode($request->getPayload(), true);
        if (!is_array($payload)) {
            return new Response(
                json_encode(['error' => 'invalid payload']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $updateData = [];

        if (array_key_exists('alt_text', $payload)) {
            $altText = $payload['alt_text'];
            if ($altText !== null && !is_string($altText)) {
                return new Response(
                    json_encode(['error' => 'alt_text must be a string or null']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $altText = $altText !== null ? trim($altText) : null;
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

            $updateData['alt_text'] = $altText;
        }

        if (array_key_exists('document_id', $payload)) {
            $documentId = $payload['document_id'];
            if ($documentId === null || $documentId === '') {
                $updateData['document_id'] = null;
            } else {
                $documentId = (int) $documentId;
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

                $updateData['document_id'] = $documentId;
            }
        }

        if (empty($updateData)) {
            return new Response(
                json_encode(['error' => 'nothing to update']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $updatedMedia = $mediaRepository->updateMedia($id, $updateData);
        if ($updatedMedia === null) {
            return new Response(
                json_encode(['error' => 'unable to update media']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $authorizedUser->getId(),
            'update',
            'media',
            $updatedMedia->getId(),
            [
                'alt_text' => $media->alt_text,
                'document_id' => $media->document_id,
            ],
            [
                'alt_text' => $updatedMedia->alt_text,
                'document_id' => $updatedMedia->document_id,
            ]
        );

        return new Response(
            json_encode([
                'id' => $updatedMedia->id,
                'document_id' => $updatedMedia->document_id,
                'uploaded_by' => $updatedMedia->uploaded_by,
                'filename' => $updatedMedia->filename,
                'alt_text' => $updatedMedia->alt_text,
                'mime_type' => $updatedMedia->mime_type,
                'file_size' => $updatedMedia->file_size,
                'path' => $updatedMedia->path,
                'created_at' => $updatedMedia->created_at,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>