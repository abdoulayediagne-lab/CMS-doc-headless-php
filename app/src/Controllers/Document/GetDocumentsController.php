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

        // Pagination par query string : ?page=1&limit=20&status=published
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? null;

        // Seuls les admins et éditeurs voient tous les statuts
        // Les auteurs ne voient que leurs propres brouillons + tous les publiés
        if (!in_array($user->getRole(), ['admin', 'editor'])) {
            $status = 'published';
        }

        $documents = $documentRepository->findAllPaginated($limit, $offset, $status);
        $total = $documentRepository->countAll($status);

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