<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class AuditLog extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int')]
    #[References(class: User::class, property: 'id')]
    public int $user_id;

    #[Column(type: 'varchar', size: 50)]
    public string $action;

    #[Column(type: 'varchar', size: 50)]
    public string $entity_type;

    #[Column(type: 'int')]
    public int $entity_id;

    #[Column(type: 'text', nullable: true)]
    public ?string $old_values = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $new_values = null;

    #[Column(type: 'timestamp')]
    public string $created_at;

    public function getId(): int {
        return $this->id;
    }

    public function getAction(): string {
        return $this->action;
    }

    public function getEntityType(): string {
        return $this->entity_type;
    }

    public function getOldValues(): ?array {
        return $this->old_values ? json_decode($this->old_values, true) : null;
    }

    public function getNewValues(): ?array {
        return $this->new_values ? json_decode($this->new_values, true) : null;
    }
}

?>