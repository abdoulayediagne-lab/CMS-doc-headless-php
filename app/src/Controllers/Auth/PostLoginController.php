<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\JwtService;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;

class PostLoginController extends AbstractController {
    public function process(Request $request): Response {
        $payload = json_decode($request->getPayload(), true);

        if (!is_array($payload) || empty($payload['email']) || empty($payload['password'])) {
            return new Response(
                json_encode(['error' => 'email and password are required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $email = strtolower(trim((string) $payload['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
            return new Response(
                json_encode(['error' => 'invalid email format']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $userRepository = new UserRepository();
        $user = $userRepository->findByEmail($email);

        if ($user === null || !$user->verifyPassword((string) $payload['password'])) {
            return new Response(
                json_encode(['error' => 'invalid credentials']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        if (!$user->isActive()) {
            return new Response(
                json_encode(['error' => 'user is inactive']),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        $jwtService = new JwtService();
        $token = $jwtService->generateToken([
            'sub' => $user->getId(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ]);

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $user->getId(),
            'login',
            'user',
            $user->getId(),
            null,
            ['email' => $user->getEmail()]
        );

        return new Response(
            json_encode([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                ],
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>