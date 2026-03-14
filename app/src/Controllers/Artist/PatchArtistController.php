<?php

namespace App\Controllers\Artist;

use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArtistRepository;

class PatchArtistController extends AbstractController {
    public function process(Request $request): Response
    {
        $artistRepository = new ArtistRepository();

        $artist = $artistRepository->find($request->getSlug('id'));

        if(empty($artist)) {
            return new Response(json_encode(['error' => 'not found']), 404, ['Content-Type' => 'application/json']);
        }
        
        $artist->name = 'New name';

        $artistRepository->update($artist);

        return new Response(json_encode($artist), 200, ['Content-Type' => 'application/json']);
    }
    
}

?>
