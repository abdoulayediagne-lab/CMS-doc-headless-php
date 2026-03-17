<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Annotations\ORM\References;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class Document extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'int', nullable: true)]
    #[References(class: Section::class, property: 'id')]
    public ?int $section_id = null;

    #[Column(type: 'int')]
    #[References(class: User::class, property: 'id')]
    public int $author_id;

    #[Column(type: 'varchar', size: 255)]
    public string $title;

    #[Column(type: 'varchar', size: 280)]
    public string $slug;

    #[Column(type: 'text')]
    public string $content = '';

    #[Column(type: 'varchar', size: 20)]
    public string $status = 'draft';

    #[Column(type: 'varchar', size: 255, nullable: true)]
    public ?string $meta_title = null;

    #[Column(type: 'varchar', size: 500, nullable: true)]
    public ?string $meta_description = null;

    #[Column(type: 'int')]
    public int $sort_order = 0;

    #[Column(type: 'timestamp', nullable: true)]
    public ?string $published_at = null;

    #[Column(type: 'timestamp')]
    public string $created_at;

    #[Column(type: 'timestamp')]
    public string $updated_at;

    public function getId(): int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getSlug(): string {
        return $this->slug;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function isPublished(): bool {
        return $this->status === 'published';
    }

    public function isDraft(): bool {
        return $this->status === 'draft';
    }

    public function publish(): void {
        $this->status = 'published';
        $this->published_at = date('Y-m-d H:i:s');
    }

    public function archive(): void {
        $this->status = 'archived';
    }
}

?>