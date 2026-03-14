<?php



namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Entities\AbstractEntity;


#[ORM]
class Artist extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;
    
    #[Column(type: 'varchar', size: 255)]
    public string $name;

    #[Column(type: 'varchar', size: 255)]
    public string $label;
    
    #[Column(type: 'varchar', size: 255)]
    public string $country;

    public function getId(): int
    {
        return $this->id;
    }
  
}

?>
