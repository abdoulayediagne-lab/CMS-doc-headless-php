<?php

namespace App\Controllers\Admin;

use App\Lib\Controllers\AbstractController;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Security\AuthGuard;
use App\Repositories\PageViewRepository;

class GetAnalyticsController extends AbstractController {
    public function process(Request $request): Response {
        $authGuard = new AuthGuard();
        $authorizedUser = $authGuard->authorize($request, ['admin']);
        if ($authorizedUser instanceof Response) {
            return $authorizedUser;
        }

        $pageViewRepository = new PageViewRepository();
        $totalViews = $pageViewRepository->countViews();
        $topDocuments = $pageViewRepository->getTopViewedDocuments(5);

        return new Response(
            json_encode([
                'total_views' => $totalViews,
                'top_documents' => $topDocuments,
            ]),
            200,
            ['Content-Type' => 'application/json']
        );
    }
}

?>