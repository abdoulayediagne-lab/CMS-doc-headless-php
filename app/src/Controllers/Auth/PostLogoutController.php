<?php

namespace App\Controllers\Auth;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;

class PostLogoutController extends AbstractController {
    public function process(Request $request): Response {
        $token = $request->getBearerToken();
        if ($token === null || $token === '') {
            return new Response(
                json_encode(['error' => 'missing bearer token']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        // Stateless JWT logout: client must discard token.
        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}

?>