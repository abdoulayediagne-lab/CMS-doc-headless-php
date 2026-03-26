<?php

namespace App\Controllers\Document;

use App\Lib\Cache\FileCache;
use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentRepository;

class DeleteDocumentController extends AbstractController
{

    public function process(Request $request): Response
    {
        // Admin et editor peuvent supprimer n'importe quel document.
        // Author peut supprimer uniquement ses propres documents.
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request, ['admin', 'editor', 'author']);
        if ($user instanceof Response) {
            return $user;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid document id']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $documentRepository = new DocumentRepository();
        $document = $documentRepository->findById($id);

        if ($document === null) {
            return new Response(
                json_encode(['error' => 'document not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        if ($user->getRole() === 'author' && $document->author_id !== $user->getId()) {
            return new Response(
                json_encode(['error' => 'forbidden: authors can only delete their own documents']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $oldValues = [
            'title' => $document->title,
            'slug' => $document->slug,
            'status' => $document->status,
            'section_id' => $document->section_id,
        ];

        $deleted = $documentRepository->deleteDocument($id);

        if ($deleted === false) {
            return new Response(
                json_encode(['error' => 'unable to delete document']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $user->getId(),
            'delete',
            'document',
            $id,
            $oldValues,
            null
        );

        try {
            (new FileCache())->deleteByPrefix('public');
        } catch (\Throwable $exception) {
            // Cache invalidation failure should not block write operations.
        }

        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}
