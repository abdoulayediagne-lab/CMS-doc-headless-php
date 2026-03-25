<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\MediaRepository;

class GetMediaController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin', 'editor', 'author']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $queryParams = $request->getUrlParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $documentId = isset($queryParams['document_id']) ? (int) $queryParams['document_id'] : null;
        $search = isset($queryParams['q']) ? trim((string) $queryParams['q']) : null;

        if ($documentId !== null && $documentId <= 0) {
            return new Response(
                json_encode(['error' => 'invalid document_id filter']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $mediaRepository = new MediaRepository();
        $media = $mediaRepository->findPaginated($limit, $offset, $documentId, $search);
        $total = $mediaRepository->countAll($documentId, $search);

        return new Response(
            json_encode([
                'data' => $media,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
                'filters' => [
                    'document_id' => $documentId,
                    'q' => $search,
                ],
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>