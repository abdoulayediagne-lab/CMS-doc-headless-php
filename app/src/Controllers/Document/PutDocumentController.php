<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;

class PutDocumentController extends AbstractController {

    public function process(Request $request): Response {
        // Seuls admin, editor et author peuvent modifier
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

        // Un auteur ne peut modifier que ses propres documents
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
        }

        $updated = $documentRepository->updateDocument($id, $payload);

        if ($updated === null) {
            return new Response(
                json_encode(['error' => 'unable to update document']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $updated->getId(),
                'title' => $updated->getTitle(),
                'slug' => $updated->getSlug(),
                'status' => $updated->getStatus(),
                'updated_at' => $updated->updated_at,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>