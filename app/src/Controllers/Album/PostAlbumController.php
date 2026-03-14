<?php
namespace App\Controllers\Album;

use App\Entities\Album;
use App\Repositories\AlbumRepository;
use App\Lib\Http\Request;
use App\Lib\Http\Response;
use App\Lib\Controllers\AbstractController;

class PostAlbumController extends AbstractController {
    public function process(Request $request): Response
    {
        $albumRepository = new AlbumRepository();

        $album= new Album();
        $album->releaseDate = (new \DateTime())->getTimestamp();
        $album->name = 'new album';
        $album->artist = 2;

        $album->id = $albumRepository->save($album);

        return new Response(json_encode($album), 201, ['Content-Type' => 'application/json']);
    }
    
}

?>
