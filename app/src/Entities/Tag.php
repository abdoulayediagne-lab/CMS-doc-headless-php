<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class Tag extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'varchar', size: 50)]
    public string $name;

    #[Column(type: 'varchar', size: 60)]
    public string $slug;

    #[Column(type: 'timestamp')]
    public string $created_at;

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSlug(): string {
        return $this->slug;
    }
}

?>