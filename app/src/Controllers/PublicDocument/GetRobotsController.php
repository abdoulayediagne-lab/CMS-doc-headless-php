<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;

class GetRobotsController extends AbstractController {

    public function process(Request $request): Response {
        $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8080');

        $robots = "User-agent: *\n";
        $robots .= "Allow: /public/\n";
        $robots .= "Allow: /sitemap.xml\n";
        $robots .= "\n";
        $robots .= "Disallow: /auth/\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /documents/\n";
        $robots .= "Disallow: /me\n";
        $robots .= "\n";
        $robots .= "Sitemap: $baseUrl/sitemap.xml\n";

        return new Response(
            $robots,
            200,
            ['Content-Type' => 'text/plain']
        );
    }
}

?>