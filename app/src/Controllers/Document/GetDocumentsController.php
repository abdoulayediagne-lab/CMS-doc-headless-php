<?php

namespace App\Controllers\Document;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\DocumentRepository;

class GetDocumentsController extends AbstractController {

    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request);
        if ($user instanceof Response) {
            return $user;
        }

        $documentRepository = new DocumentRepository();

        $queryParams = $request->getUrlParams();

        // Pagination par query string : ?page=1&limit=20&status=published
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = isset($queryParams['status']) ? trim((string) $queryParams['status']) : null;

        $allowedStatuses = ['draft', 'review', 'published', 'archived'];
        if ($status !== null && !in_array($status, $allowedStatuses, true)) {
            return new Response(
                json_encode(['error' => 'invalid status filter']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if ($user->getRole() === 'admin') {
            $documents = $documentRepository->findAllPaginated($limit, $offset, $status);
            $total = $documentRepository->countAll($status);
        } elseif ($user->getRole() === 'editor') {
            $documents = $documentRepository->findVisibleForEditor($user->getId(), $limit, $offset, $status);
            $total = $documentRepository->countVisibleForEditor($user->getId(), $status);
        } else {
            $documents = $documentRepository->findAllPaginated($limit, $offset, 'published');
            $total = $documentRepository->countAll('published');
        }

        return new Response(
            json_encode([
                'data' => $documents,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit),
                ],
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>