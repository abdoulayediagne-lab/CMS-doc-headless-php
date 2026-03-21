<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;

class DeleteDocumentController extends AbstractController {

    public function process(Request $request): Response {
        // Seuls admin et editor peuvent supprimer
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request, ['admin', 'editor']);
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

        $deleted = $documentRepository->deleteDocument($id);

        if ($deleted === false) {
            return new Response(
                json_encode(['error' => 'unable to delete document']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}

?>