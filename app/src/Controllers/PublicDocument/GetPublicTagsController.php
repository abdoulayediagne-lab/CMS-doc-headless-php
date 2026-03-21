<?php

namespace App\Controllers\PublicDocument;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Repositories\TagRepository;

class GetPublicTagsController extends AbstractController {

    public function process(Request $request): Response {
        // Endpoint public : liste tous les tags avec le nombre de documents publiés associés

        $tagRepository = new TagRepository();
        $tags = $tagRepository->findAllWithPublishedCount();

        return new Response(
            json_encode([
                'data' => $tags,
                'total' => count($tags),
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>