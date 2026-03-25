<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\AuditLogRepository;
use App\Repositories\UserRepository;

class DeleteMeController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request);
        if ($user instanceof Response) {
            return $user;
        }

        $userId = $user->getId();

        $userRepository = new UserRepository();
        $anonymized = $userRepository->anonymizeById($userId);

        if ($anonymized === null) {
            return new Response(
                json_encode(['error' => 'unable to anonymize user']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        $auditLogRepository = new AuditLogRepository();
        $auditLogRepository->logAction(
            $userId,
            'erase_account',
            'user',
            $userId,
            [
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ],
            [
                'anonymized' => true,
                'is_active' => false,
            ]
        );

        return new Response(
            json_encode([
                'success' => true,
                'message' => 'account anonymized',
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>