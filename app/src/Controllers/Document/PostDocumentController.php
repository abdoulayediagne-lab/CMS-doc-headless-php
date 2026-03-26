<?php

namespace App\Controllers\Document;

use App\Lib\Cache\FileCache;
use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\SectionRepository;

class PostDocumentController extends AbstractController
{

    public function process(Request $request): Response
    {
        // Admin, editor et author peuvent créer des documents
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request, ['admin', 'editor', 'author']);
        if ($user instanceof Response) {
            return $user;
        }

        $payload = json_decode($request->getPayload(), true);

        if (!is_array($payload) || empty($payload['title'])) {
            return new Response(
                json_encode(['error' => 'title is required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $allowedKeys = ['title', 'slug', 'content', 'status', 'section_id', 'meta_title', 'meta_description', 'sort_order', 'tags'];
        $unexpectedKeys = array_diff(array_keys($payload), $allowedKeys);
        if (!empty($unexpectedKeys)) {
            return new Response(
                json_encode(['error' => 'unexpected fields: ' . implode(', ', $unexpectedKeys)]),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $title = trim((string) $payload['title']);
        if ($title === '' || mb_strlen($title) > 255) {
            return new Response(
                json_encode(['error' => 'title must be between 1 and 255 chars']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $slugInput = array_key_exists('slug', $payload) ? (string) $payload['slug'] : $title;
        $slug = $this->generateSlug($slugInput);
        if ($slug === '' || mb_strlen($slug) > 280) {
            return new Response(
                json_encode(['error' => 'invalid slug']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $status = $payload['status'] ?? 'draft';
        if (!is_string($status)) {
            return new Response(
                json_encode(['error' => 'status must be a string']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if (array_key_exists('tags', $payload) && !is_array($payload['tags'])) {
            return new Response(
                json_encode(['error' => 'tags must be an array']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $tagSlugs = $this->normalizeTagSlugs($payload['tags'] ?? []);

        $content = $payload['content'] ?? '';
        if (!is_string($content)) {
            return new Response(
                json_encode(['error' => 'content must be a string']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $sectionId = null;
        if (array_key_exists('section_id', $payload) && $payload['section_id'] !== null && $payload['section_id'] !== '') {
            $sectionId = filter_var($payload['section_id'], FILTER_VALIDATE_INT);
            if ($sectionId === false || $sectionId <= 0) {
                return new Response(
                    json_encode(['error' => 'section_id must be a positive integer']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $sectionRepository = new SectionRepository();
            if ($sectionRepository->findById((int) $sectionId) === null) {
                return new Response(
                    json_encode(['error' => 'section not found']),
                    404,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $metaTitle = $payload['meta_title'] ?? null;
        if ($metaTitle !== null) {
            if (!is_string($metaTitle)) {
                return new Response(
                    json_encode(['error' => 'meta_title must be a string or null']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $metaTitle = trim($metaTitle);
            if ($metaTitle === '') {
                $metaTitle = null;
            } elseif (mb_strlen($metaTitle) > 255) {
                return new Response(
                    json_encode(['error' => 'meta_title must be <= 255 chars']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $metaDescription = $payload['meta_description'] ?? null;
        if ($metaDescription !== null) {
            if (!is_string($metaDescription)) {
                return new Response(
                    json_encode(['error' => 'meta_description must be a string or null']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $metaDescription = trim($metaDescription);
            if ($metaDescription === '') {
                $metaDescription = null;
            } elseif (mb_strlen($metaDescription) > 500) {
                return new Response(
                    json_encode(['error' => 'meta_description must be <= 500 chars']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $sortOrder = 0;
        if (array_key_exists('sort_order', $payload)) {
            $sortOrderCandidate = filter_var($payload['sort_order'], FILTER_VALIDATE_INT);
            if ($sortOrderCandidate === false) {
                return new Response(
                    json_encode(['error' => 'sort_order must be an integer']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $sortOrder = (int) $sortOrderCandidate;
        }

        $allowedStatuses = ['draft', 'review', 'published', 'archived'];
        if (!in_array($status, $allowedStatuses, true)) {
            return new Response(
                json_encode(['error' => 'invalid status']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        // Vérifier l'unicité du slug
        $documentRepository = new DocumentRepository();
        if ($documentRepository->findBySlug($slug) !== null) {
            return new Response(
                json_encode(['error' => 'slug already exists']),
                409,
                ['Content-Type' => 'application/json']
            );
        }

        $document = $documentRepository->create([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
            'section_id' => $sectionId,
            'author_id' => $user->getId(),
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'sort_order' => $sortOrder,
        ]);

        if ($document === null) {
            return new Response(
                json_encode(['error' => 'unable to create document']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        if (!empty($tagSlugs)) {
            $documentRepository->replaceDocumentTags($document->getId(), $tagSlugs);
        }

        $tagsByDocumentId = $documentRepository->findTagsForDocumentIds([$document->getId()]);
        $tags = $tagsByDocumentId[$document->getId()] ?? [];

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $user->getId(),
            'create',
            'document',
            $document->getId(),
            null,
            [
                'title' => $document->getTitle(),
                'status' => $document->getStatus(),
                'tags' => $tags,
            ]
        );

        try {
            (new FileCache())->deleteByPrefix('public');
        } catch (\Throwable $exception) {
            // Cache invalidation failure should not block write operations.
        }

        return new Response(
            json_encode([
                'id' => $document->getId(),
                'title' => $document->getTitle(),
                'slug' => $document->getSlug(),
                'status' => $document->getStatus(),
                'tags' => $tags,
                'author_id' => $document->author_id,
                'created_at' => $document->created_at,
            ]),
            201,
            ['Content-Type' => 'application/json']
        );
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function normalizeTagSlugs(mixed $tags): array
    {
        if (!is_array($tags)) {
            return [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                continue;
            }

            $slug = strtolower(trim($tag));
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', $slug);
            $slug = trim($slug, '-');

            if ($slug !== '') {
                $normalized[] = $slug;
            }
        }

        return array_values(array_unique($normalized));
    }
}
