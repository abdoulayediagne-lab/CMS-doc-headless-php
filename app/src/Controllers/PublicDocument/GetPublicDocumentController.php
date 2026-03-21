<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\DocumentRepository;

class GetPublicDocumentController extends AbstractController {
    public function process(Request $request): Response {
        $slug = trim((string) $request->getSlug('slug'));
        if ($slug === '') {
            return new Response(
                json_encode(['error' => 'invalid document slug']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $documentRepository = new DocumentRepository();
        $document = $documentRepository->findBySlug($slug);

        if ($document === null || !$document->isPublished()) {
            return new Response(
                json_encode(['error' => 'document not found']),
                404,
                ['Content-Type' => 'application/json']
            );
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
                'meta_title' => $document->meta_title,
                'meta_description' => $document->meta_description,
                'published_at' => $document->published_at,
                'updated_at' => $document->updated_at,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>