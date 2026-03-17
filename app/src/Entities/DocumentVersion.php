<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class DocumentVersion extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int')]
    #[References(class: Document::class, property: 'id')]
    public int $document_id;

    #[Column(type: 'int')]
    #[References(class: User::class, property: 'id')]
    public int $author_id;

    #[Column(type: 'varchar', size: 255)]
    public string $title;

    #[Column(type: 'text')]
    public string $content;

    #[Column(type: 'int')]
    public int $version_number = 1;

    #[Column(type: 'timestamp')]
    public string $created_at;

    public function getId(): int {
        return $this->id;
    }

    public function getVersionNumber(): int {
        return $this->version_number;
    }

    public function getDocumentId(): int {
        return $this->document_id;
    }
}

?>