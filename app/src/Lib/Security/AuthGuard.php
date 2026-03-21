<?php

namespace App\Lib\Security;

use App\Entities\User;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\UserRepository;

class AuthGuard {
    public function authorize(Request $request, array $allowedRoles = []): User | Response {
        $token = $request->getBearerToken();
        if ($token === null || $token === '') {
            return new Response(
                json_encode(['error' => 'missing bearer token']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        $jwtService = new JwtService();
        $payload = $jwtService->validateToken($token);
        if ($payload === null || !isset($payload['sub'])) {
            return new Response(
                json_encode(['error' => 'invalid token']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findById((int) $payload['sub']);
        if ($user === null || !$user->isActive()) {
            return new Response(
                json_encode(['error' => 'user not found']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        if (!empty($allowedRoles) && !in_array($user->getRole(), $allowedRoles, true)) {
            return new Response(
                json_encode([
                    'error' => 'forbidden',
                    'required_roles' => $allowedRoles,
                ]),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        return $user;
    }
}

?>