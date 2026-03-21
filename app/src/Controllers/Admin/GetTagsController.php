<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\TagRepository;

class GetTagsController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $queryParams = $request->getUrlParams();
        $page = max(1, (int) ($queryParams['page'] ?? 1));
        $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $tagRepository = new TagRepository();
        $tags = $tagRepository->findAllPaginated($limit, $offset);
        $total = $tagRepository->countAll();

        return new Response(
            json_encode([
                'data' => $tags,
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