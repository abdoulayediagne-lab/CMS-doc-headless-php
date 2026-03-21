<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;

class PostLogoutController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $user = $authGuard->authorize($request);
        if ($user instanceof Response) {
            return $user;
        }

        // Stateless JWT logout: client must discard token.
        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}

?>