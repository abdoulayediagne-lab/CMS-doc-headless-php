<?php



namespace App\Controllers\Artist;

use App\Entities\Artist;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;
use App\Repositories\ArtistRepository;

class PostArtistController extends AbstractController {
    public function process(Request $request): Response
    {
        $artistRepository = new ArtistRepository();

        $artist = new Artist();
        $artist->name = 'New artist';
        $artist->country = 'Oz';
        $artist->label = 'Indie';

        $artist->id = $artistRepository->save($artist);

        return new Response(json_encode($artist), 201, ['Content-Type' => 'application/json']);
    }
    
}

?>
