<?php

namespace App\Controllers\Album;

use App\Repositories\AlbumRepository;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;

class GetAlbumsController extends AbstractController {
    public function process(Request $request): Response
    {
        $albumRepository = new AlbumRepository();

        $albums = $albumRepository->findAll();

        return new Response(json_encode($albums), 200, ['Content-Type' => 'application/json']);
    }
    
}

?>
