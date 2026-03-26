<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Cache\FileCache;
use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\DocumentRepository;
use App\Repositories\PageViewRepository;

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

        $pageViewRepository = new PageViewRepository();
        $cache = new FileCache();
        $cacheKey = $cache->buildKey('public_document', ['slug' => $slug]);

        $cachedPayload = $cache->get($cacheKey);
        if ($cachedPayload !== null) {
            $cachedData = json_decode($cachedPayload, true);
            if (is_array($cachedData) && isset($cachedData['id'])) {
                $this->recordView($pageViewRepository, (int) $cachedData['id'], $request);
            }

            return new Response(
                $cachedPayload,
                200,
                [
                    'Content-Type' => 'application/json',
                    'X-Cache' => 'HIT',
                ]
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

        $this->recordView($pageViewRepository, (int) $document->getId(), $request);

        $responsePayload = json_encode([
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
        ]);

        if ($responsePayload !== false) {
            $cache->set($cacheKey, $responsePayload, 60);
        }

        return new Response(
            $responsePayload === false ? json_encode(['error' => 'unable to encode response']) : $responsePayload,
            200,
            [
                'Content-Type' => 'application/json',
                'X-Cache' => 'MISS',
            ]
        );
    }

    private function recordView(PageViewRepository $pageViewRepository, int $documentId, Request $request): void {
        try {
            $pageViewRepository->recordView(
                $documentId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $request->getHeader('User-Agent'),
                $request->getHeader('Referer')
            );
        } catch (\Throwable $exception) {
            // Keep document delivery resilient even if analytics insert fails.
        }
    }
}

?>