<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\DocumentRepository;
use App\Repositories\DocumentVersionRepository;

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

        $tagSlugs = null;
        if (array_key_exists('tags', $payload)) {
            $tagSlugs = $this->normalizeTagSlugs($payload['tags']);
            unset($payload['tags']);
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
}
