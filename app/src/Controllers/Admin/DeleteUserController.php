<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\UserRepository;

class DeleteUserController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid user id']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if ($authorizedUser->getId() === $id) {
            return new Response(
                json_encode(['error' => 'you cannot delete your own account']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $userRepository = new UserRepository();
        $existingUser = $userRepository->findById($id);
        if ($existingUser === null) {
            return new Response(
                json_encode(['error' => 'user not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        $deleted = $userRepository->deleteById($id);
        if ($deleted === false) {
            return new Response(
                json_encode(['error' => 'unable to delete user (linked data may exist)']),
                409,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}

?>
