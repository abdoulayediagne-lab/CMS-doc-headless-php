<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;

class GetDocumentController extends AbstractController
{

    public function process(Request $request): Response
    {
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request);
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

        if (!in_array($user->getRole(), ['admin', 'editor'], true)) {
            $isAuthorOwner = $user->getRole() === 'author' && $document->author_id === $user->getId();
            if (!$document->isPublished() && !$isAuthorOwner) {
                return new Response(
                    json_encode(['error' => 'document not found']),
                    404,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $tagsByDocumentId = $documentRepository->findTagsForDocumentIds([$document->getId()]);
        $tags = $tagsByDocumentId[$document->getId()] ?? [];

        return new Response(
            json_encode([
                'id' => $document->getId(),
                'title' => $document->getTitle(),
                'slug' => $document->getSlug(),
                'content' => $document->content,
                'status' => $document->getStatus(),
                'section_id' => $document->section_id,
                'tags' => $tags,
                'author_id' => $document->author_id,
                'meta_title' => $document->meta_title,
                'meta_description' => $document->meta_description,
                'sort_order' => $document->sort_order,
                'published_at' => $document->published_at ?? null,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}
