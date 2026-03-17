<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class Media extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int', nullable: true)]
    #[References(class: Document::class, property: 'id')]
    public ?int $document_id = null;

    #[Column(type: 'int')]
    #[References(class: User::class, property: 'id')]
    public int $uploaded_by;

    #[Column(type: 'varchar', size: 255)]
    public string $filename;

    #[Column(type: 'varchar', size: 255, nullable: true)]
    public ?string $alt_text = null;

    #[Column(type: 'varchar', size: 100)]
    public string $mime_type;

    #[Column(type: 'int')]
    public int $file_size = 0;

    #[Column(type: 'varchar', size: 500)]
    public string $path;

    #[Column(type: 'timestamp')]
    public string $created_at;

    public function getId(): int {
        return $this->id;
    }

    public function getFilename(): string {
        return $this->filename;
    }

    public function getAltText(): ?string {
        return $this->alt_text;
    }

    public function getPath(): string {
        return $this->path;
    }

    public function getFileSizeFormatted(): string {
        if ($this->file_size < 1024) return $this->file_size . ' o';
        if ($this->file_size < 1048576) return round($this->file_size / 1024, 1) . ' Ko';
        return round($this->file_size / 1048576, 1) . ' Mo';
    }
}

?>