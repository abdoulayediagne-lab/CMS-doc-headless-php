<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;

class PostDocumentController extends AbstractController {

    public function process(Request $request): Response {
        // Seuls admin et editor peuvent créer des documents
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request, ['admin', 'editor']);
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

        $title = trim((string) $payload['title']);
        $slug = $payload['slug'] ?? $this->generateSlug($title);
        $status = $payload['status'] ?? 'draft';

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
            'content' => $payload['content'] ?? '',
            'status' => $status,
            'section_id' => $payload['section_id'] ?? null,
            'author_id' => $user->getId(),
            'meta_title' => $payload['meta_title'] ?? null,
            'meta_description' => $payload['meta_description'] ?? null,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        if ($document === null) {
            return new Response(
                json_encode(['error' => 'unable to create document']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $document->getId(),
                'title' => $document->getTitle(),
                'slug' => $document->getSlug(),
                'status' => $document->getStatus(),
                'author_id' => $document->author_id,
                'created_at' => $document->created_at,
            ]),
            201,
            ['Content-Type' => 'application/json']
        );
    }

    private function generateSlug(string $title): string {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
}

?>