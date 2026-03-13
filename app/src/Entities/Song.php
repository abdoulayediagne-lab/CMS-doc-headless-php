<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;
use App\Entities\Album;

#[ORM]
class Song extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;
    
    #[Column(type: 'varchar', size: 255)]
    public string $name;

    #[Column(type: 'int')]
    public \DateTime $releaseDate;
    
    #[Column(type: 'int')]
    #[References(class: Artist::class, property: 'id')]
    public string $artist;
    
    #[Column(type: 'int')]
    #[References(class: Album::class, property: 'id')]
    public string $album;
    
    public function getId(): int
    {
        return $this->id;
    }
  
}

?>
