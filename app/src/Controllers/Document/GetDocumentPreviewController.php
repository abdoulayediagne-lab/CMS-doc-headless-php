<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;

class GetDocumentPreviewController extends AbstractController
{

    public function process(Request $request): Response
    {
        // Aperçu avant publication (exigé par la consigne)
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

        // Un author ne peut prévisualiser que ses propres documents
        if ($user->getRole() === 'author' && $document->author_id !== $user->getId()) {
            return new Response(
                json_encode(['error' => 'forbidden']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $tagsByDocumentId = $documentRepository->findTagsForDocumentIds([$id]);
        $tags = $tagsByDocumentId[$id] ?? [];

        // Retourne le document tel qu'il apparaîtrait une fois publié
        return new Response(
            json_encode([
                'preview' => true,
                'document' => [
                    'id' => $document->getId(),
                    'title' => $document->getTitle(),
                    'slug' => $document->getSlug(),
                    'content' => $document->content,
                    'status' => $document->getStatus(),
                    'author_id' => $document->author_id,
                    'section_id' => $document->section_id,
                    'tags' => $tags,
                    'meta_title' => $document->meta_title ?? $document->getTitle(),
                    'meta_description' => $document->meta_description ?? '',
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                ],
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
