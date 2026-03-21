<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\TagRepository;

class DeleteTagController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid tag id']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $tagRepository = new TagRepository();
        $tag = $tagRepository->findById($id);
        if ($tag === null) {
            return new Response(
                json_encode(['error' => 'tag not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        $deleted = $tagRepository->deleteTag($id);
        if ($deleted === false) {
            return new Response(
                json_encode(['error' => 'unable to delete tag']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}

?>