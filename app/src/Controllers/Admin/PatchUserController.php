<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\UserRepository;

class PatchUserController extends AbstractController {
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

        $payload = json_decode($request->getPayload(), true);
        if (!is_array($payload)) {
            return new Response(
                json_encode(['error' => 'invalid payload']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $role = null;
        if (array_key_exists('role', $payload)) {
            $role = (string) $payload['role'];
            $allowedRoles = ['reader', 'author', 'editor', 'admin'];
            if (!in_array($role, $allowedRoles, true)) {
                return new Response(
                    json_encode(['error' => 'invalid role']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $isActive = null;
        if (array_key_exists('is_active', $payload)) {
            if (!is_bool($payload['is_active'])) {
                return new Response(
                    json_encode(['error' => 'is_active must be a boolean']),
                    400,
                    ['Content-Type' => 'application/json']
                );
            }
            $isActive = (bool) $payload['is_active'];
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

        $updatedUser = $userRepository->updateAdminFields($id, $role, $isActive);

        if ($updatedUser === null) {
            return new Response(
                json_encode(['error' => 'unable to update user']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response(
            json_encode([
                'id' => $updatedUser->getId(),
                'username' => $updatedUser->getUsername(),
                'email' => $updatedUser->getEmail(),
                'role' => $updatedUser->getRole(),
                'is_active' => $updatedUser->isActive(),
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>