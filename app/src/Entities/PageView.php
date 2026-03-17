<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class PageView extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int')]
    #[References(class: Document::class, property: 'id')]
    public int $document_id;

    #[Column(type: 'varchar', size: 45, nullable: true)]
    public ?string $ip_address = null;

    #[Column(type: 'varchar', size: 500, nullable: true)]
    public ?string $user_agent = null;

    #[Column(type: 'varchar', size: 500, nullable: true)]
    public ?string $referer = null;

    #[Column(type: 'timestamp')]
    public string $viewed_at;

    public function getId(): int {
        return $this->id;
    }

    public function getDocumentId(): int {
        return $this->document_id;
    }

    public function getViewedAt(): string {
        return $this->viewed_at;
    }
}

?>