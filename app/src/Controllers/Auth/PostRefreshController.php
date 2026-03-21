<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\JwtService;
use App\Repositories\UserRepository;

class PostRefreshController extends AbstractController {
    public function process(Request $request): Response {
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

        $newToken = $jwtService->generateToken([
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ]);

        return new Response(
            json_encode([
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>