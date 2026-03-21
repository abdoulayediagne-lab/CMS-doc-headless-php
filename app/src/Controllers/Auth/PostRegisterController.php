<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Lib\Security\JwtService;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;

class PostRegisterController extends AbstractController {
    public function process(Request $request): Response {
        $userRepository = new UserRepository();
        $actorId = null;

        // Bootstrap rule: first account can be created without admin token.
        if ($userRepository->countUsers() > 0) {
            $authGuard = new AuthGuard();
            $authorizedUser = $authGuard->authorize($request, ['admin']);
            if ($authorizedUser instanceof Response) {
                return $authorizedUser;
            }
            $actorId = $authorizedUser->getId();
        }

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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response(
                json_encode(['error' => 'invalid email format']),
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
            $actorId ?? $user->getId(),
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