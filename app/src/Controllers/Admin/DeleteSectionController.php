<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\SectionRepository;

class DeleteSectionController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $id = (int) $request->getSlug('id');
        if ($id <= 0) {
            return new Response(
                json_encode(['error' => 'invalid section id']),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $sectionRepository = new SectionRepository();
        $section = $sectionRepository->findById($id);
        if ($section === null) {
            return new Response(
                json_encode(['error' => 'section not found']),
                404,
                ['Content-Type' => 'application/json']
            );
        }

        $deleted = $sectionRepository->deleteSection($id);
        if ($deleted === false) {
            return new Response(
                json_encode(['error' => 'unable to delete section']),
                500,
                ['Content-Type' => 'application/json']
            );
        }

        return new Response('', 204, ['Content-Type' => 'application/json']);
    }
}

?>