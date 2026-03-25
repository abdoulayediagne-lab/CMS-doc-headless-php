<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\MediaRepository;

class DeleteMediaController extends AbstractController {
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
                json_encode(['error' => 'forbidden: you can only delete your own media']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $absolutePath = dirname(__DIR__, 3) . $media->path;

        $deleted = $mediaRepository->deleteMedia($id);
        if (!$deleted) {
            return new Response(
                json_encode(['error' => 'unable to delete media']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $authorizedUser->getId(),
            'delete',
            'media',
            $id,
            [
                'filename' => $media->filename,
                'path' => $media->path,
                'uploaded_by' => $media->uploaded_by,
                'document_id' => $media->document_id,
            ],
            null
        );

        return new Response(
            json_encode([
                'success' => true,
                'deleted_id' => $id,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>