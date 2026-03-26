<?php

namespace App\Controllers\Document;

use App\Lib\Cache\FileCache;
use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\DocumentVersionRepository;
use App\Repositories\SectionRepository;

class PutDocumentController extends AbstractController
{

    private const TRANSITIONS = [
        'draft'     => ['review'],
        'review'    => ['published', 'draft'],
        'published' => ['archived', 'draft'],
        'archived'  => ['draft'],
    ];

    public function process(Request $request): Response
    {
        // Admin, editor et author peuvent modifier
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

        $oldValues = [
            'title' => $document->title,
            'slug' => $document->slug,
            'content' => $document->content,
            'status' => $document->status,
            'section_id' => $document->section_id,
            'meta_title' => $document->meta_title,
            'meta_description' => $document->meta_description,
            'sort_order' => $document->sort_order,
        ];

        // Un author ne peut modifier que ses propres documents
        if ($user->getRole() === 'author' && $document->author_id !== $user->getId()) {
            return new Response(
                json_encode(['error' => 'forbidden: you can only edit your own documents']),
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

        $allowedKeys = ['title', 'slug', 'content', 'status', 'section_id', 'meta_title', 'meta_description', 'sort_order', 'tags'];
        $unexpectedKeys = array_diff(array_keys($payload), $allowedKeys);
        if (!empty($unexpectedKeys)) {
            return new Response(
                json_encode(['error' => 'unexpected fields: ' . implode(', ', $unexpectedKeys)]),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $tagSlugs = null;
        if (array_key_exists('tags', $payload)) {
            if (!is_array($payload['tags'])) {
                return new Response(
                    json_encode(['error' => 'tags must be an array']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $tagSlugs = $this->normalizeTagSlugs($payload['tags']);
            unset($payload['tags']);
        }

        if (array_key_exists('title', $payload)) {
            $title = trim((string) $payload['title']);
            if ($title === '' || mb_strlen($title) > 255) {
                return new Response(
                    json_encode(['error' => 'title must be between 1 and 255 chars']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $payload['title'] = $title;
        }

        if (array_key_exists('slug', $payload)) {
            $slug = $this->normalizeSlug((string) $payload['slug']);
            if ($slug === '' || mb_strlen($slug) > 280) {
                return new Response(
                    json_encode(['error' => 'invalid slug']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $payload['slug'] = $slug;
        }

        if (array_key_exists('content', $payload) && !is_string($payload['content'])) {
            return new Response(
                json_encode(['error' => 'content must be a string']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if (array_key_exists('section_id', $payload)) {
            if ($payload['section_id'] === null || $payload['section_id'] === '') {
                $payload['section_id'] = null;
            } else {
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

                $payload['section_id'] = (int) $sectionId;
            }
        }

        if (array_key_exists('meta_title', $payload)) {
            if ($payload['meta_title'] !== null && !is_string($payload['meta_title'])) {
                return new Response(
                    json_encode(['error' => 'meta_title must be a string or null']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $metaTitle = $payload['meta_title'] !== null ? trim((string) $payload['meta_title']) : null;
            if ($metaTitle !== null && $metaTitle !== '' && mb_strlen($metaTitle) > 255) {
                return new Response(
                    json_encode(['error' => 'meta_title must be <= 255 chars']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $payload['meta_title'] = $metaTitle === '' ? null : $metaTitle;
        }

        if (array_key_exists('meta_description', $payload)) {
            if ($payload['meta_description'] !== null && !is_string($payload['meta_description'])) {
                return new Response(
                    json_encode(['error' => 'meta_description must be a string or null']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $metaDescription = $payload['meta_description'] !== null ? trim((string) $payload['meta_description']) : null;
            if ($metaDescription !== null && $metaDescription !== '' && mb_strlen($metaDescription) > 500) {
                return new Response(
                    json_encode(['error' => 'meta_description must be <= 500 chars']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $payload['meta_description'] = $metaDescription === '' ? null : $metaDescription;
        }

        if (array_key_exists('sort_order', $payload)) {
            $sortOrder = filter_var($payload['sort_order'], FILTER_VALIDATE_INT);
            if ($sortOrder === false) {
                return new Response(
                    json_encode(['error' => 'sort_order must be an integer']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $payload['sort_order'] = (int) $sortOrder;
        }

        if (isset($payload['status'])) {
            $allowedStatuses = ['draft', 'review', 'published', 'archived'];
            if (!in_array($payload['status'], $allowedStatuses, true)) {
                return new Response(
                    json_encode(['error' => 'invalid status. Allowed: draft, review, published, archived']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }

            $currentStatus = $document->getStatus();
            $newStatus = $payload['status'];

            if ($currentStatus !== $newStatus && $user->getRole() !== 'admin') {
                $allowedTransitions = self::TRANSITIONS[$currentStatus] ?? [];
                if (!in_array($newStatus, $allowedTransitions, true)) {
                    return new Response(
                        json_encode([
                            'error' => "invalid transition: $currentStatus → $newStatus",
                            'allowed_transitions' => $allowedTransitions,
                        ]),
                        422,
                        ['Content-Type' => 'application/json']
                    );
                }
            }
        }

        // Si le slug change, vérifier l'unicité
        if (isset($payload['slug']) && $payload['slug'] !== $document->getSlug()) {
            if ($documentRepository->findBySlug($payload['slug']) !== null) {
                return new Response(
                    json_encode(['error' => 'slug already exists']),
                    409,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        // Gérer la publication (mettre published_at si le statut passe à published)
        if (isset($payload['status']) && $payload['status'] === 'published' && !$document->isPublished()) {
            $payload['published_at'] = date('Y-m-d H:i:s');
        } elseif (isset($payload['status']) && $payload['status'] !== 'published') {
            $payload['published_at'] = null;
        }

        // Créer une version avant la modification (historique/versioning - exigé par la consigne)
        $versionRepository = new DocumentVersionRepository();
        $versionNumber = $versionRepository->createVersion(
            $document->getId(),
            $user->getId(),
            $document->title,
            $document->content
        );

        $updated = $documentRepository->updateDocument($id, $payload);

        if ($updated === null) {
            return new Response(
                json_encode(['error' => 'unable to update document']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        if ($tagSlugs !== null) {
            $documentRepository->replaceDocumentTags($id, $tagSlugs);
        }

        $tagsByDocumentId = $documentRepository->findTagsForDocumentIds([$updated->getId()]);
        $tags = $tagsByDocumentId[$updated->getId()] ?? [];

        // Audit log
        $auditLogRepository = new AuditLogRepository();
        $action = 'update';
        if (isset($payload['status']) && $payload['status'] !== $oldValues['status']) {
            $action = 'status_change:' . $oldValues['status'] . '→' . $payload['status'];
        }

        $auditLogRepository->logAction(
            $user->getId(),
            $action,
            'document',
            $updated->getId(),
            $oldValues,
            [
                'title' => $updated->title,
                'slug' => $updated->slug,
                'status' => $updated->status,
                'section_id' => $updated->section_id,
                'meta_title' => $updated->meta_title,
                'meta_description' => $updated->meta_description,
                'sort_order' => $updated->sort_order,
                'tags' => $tags,
                'version' => $versionNumber,
            ]
        );

        try {
            (new FileCache())->deleteByPrefix('public');
        } catch (\Throwable $exception) {
            // Cache invalidation failure should not block write operations.
        }

        return new Response(
            json_encode([
                'id' => $updated->getId(),
                'title' => $updated->getTitle(),
                'slug' => $updated->getSlug(),
                'status' => $updated->getStatus(),
                'version' => $versionNumber,
                'tags' => $tags,
                'updated_at' => $updated->updated_at,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
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

    private function normalizeSlug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        return trim($slug, '-');
    }
}
