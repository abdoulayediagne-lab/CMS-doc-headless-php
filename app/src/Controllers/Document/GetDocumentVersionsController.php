<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;
use App\Repositories\DocumentVersionRepository;

class GetDocumentVersionsController extends AbstractController {

    public function process(Request $request): Response {
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

        // Un author/editor ne voit que les versions de ses propres documents
        if (in_array($user->getRole(), ['editor', 'author']) && $document->author_id !== $user->getId()) {
            return new Response(
                json_encode(['error' => 'forbidden']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $versionRepository = new DocumentVersionRepository();
        $versions = $versionRepository->findByDocumentId($id);

        return new Response(
            json_encode([
                'document_id' => $id,
                'current_title' => $document->getTitle(),
                'current_status' => $document->getStatus(),
                'versions' => $versions,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>