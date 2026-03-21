<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\UserRepository;

class GetUsersController extends AbstractController {
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

        $userRepository = new UserRepository();
        $users = $userRepository->findAllPaginated($limit, $offset);
        $total = $userRepository->countUsers();

        return new Response(
            json_encode([
                'data' => $users,
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