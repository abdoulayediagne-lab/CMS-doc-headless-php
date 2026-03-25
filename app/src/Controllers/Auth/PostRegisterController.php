<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\JwtService;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;

class PostRegisterController extends AbstractController {
    public function process(Request $request): Response {
        $userRepository = new UserRepository();

        $payload = json_decode($request->getPayload(), true);

        if (!is_array($payload) || empty($payload['username']) || empty($payload['email']) || empty($payload['password'])) {
            return new Response(
                json_encode(['error' => 'username, email and password are required']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $username = trim((string) $payload['username']);
        $email = strtolower(trim((string) $payload['email']));
        $password = (string) $payload['password'];

        if ($username === '' || mb_strlen($username) < 2 || mb_strlen($username) > 50) {
            return new Response(
                json_encode(['error' => 'username must be between 2 and 50 characters']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            return new Response(
                json_encode(['error' => 'username contains invalid characters']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response(
                json_encode(['error' => 'invalid email format']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if (mb_strlen($email) > 255) {
            return new Response(
                json_encode(['error' => 'email must be <= 255 characters']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if (strlen($password) < 8) {
            return new Response(
                json_encode(['error' => 'password must be at least 8 characters']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        if ($userRepository->findByEmail($email) !== null) {
            return new Response(
                json_encode(['error' => 'email already in use']),
                409,
                ['Content-Type' => 'application/json']
            );
        }

        $user = $userRepository->create($username, $email, $password);
        if ($user === null) {
            return new Response(
                json_encode(['error' => 'unable to create user']),
                500,
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
            'register',
            'user',
            $user->getId(),
            null,
            [
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
            ]
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
            201,
            ['Content-Type' => 'application/json']
        );
    }
}

?>