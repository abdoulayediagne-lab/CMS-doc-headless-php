<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Lib\Security\JwtService;

class PostRefreshController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request);
        if ($user instanceof Response) {
            return $user;
        }

        $jwtService = new JwtService();
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