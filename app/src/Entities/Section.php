<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class Section extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int', nullable: true)]
    #[References(class: Section::class, property: 'id')]
    public ?int $parent_id = null;

    #[Column(type: 'varchar', size: 100)]
    public string $name;

    #[Column(type: 'varchar', size: 120)]
    public string $slug;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'int')]
    public int $sort_order = 0;

    #[Column(type: 'timestamp')]
    public string $created_at;

    #[Column(type: 'timestamp')]
    public string $updated_at;

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSlug(): string {
        return $this->slug;
    }

    public function getParentId(): ?int {
        return $this->parent_id;
    }

    public function hasParent(): bool {
        return $this->parent_id !== null;
    }
}

?>