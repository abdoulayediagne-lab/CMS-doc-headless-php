<?php

namespace App\Repositories;

use App\Entities\Tag;
use App\Lib\Repositories\AbstractRepository;

class TagRepository extends AbstractRepository {
    public function getTable(): string {
        return 'tags';
    }

    public function findById(int $id): ?Tag {
        $query = 'SELECT * FROM tags WHERE id = :id LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['id' => $id]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Tag::class);

        $tag = $statement->fetch();
        return $tag === false ? null : $tag;
    }

    public function findBySlug(string $slug): ?Tag {
        $query = 'SELECT * FROM tags WHERE slug = :slug LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['slug' => $slug]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Tag::class);

        $tag = $statement->fetch();
        return $tag === false ? null : $tag;
    }

    public function findAllPaginated(int $limit = 50, int $offset = 0): array {
        $query = 'SELECT * FROM tags ORDER BY name ASC LIMIT :limit OFFSET :offset';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        $query = 'SELECT COUNT(*) FROM tags';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function create(string $name, string $slug): ?Tag {
        $query = 'INSERT INTO tags (name, slug) VALUES (:name, :slug)';
        $statement = $this->db->getConnexion()->prepare($query);
        $created = $statement->execute([
            'name' => $name,
            'slug' => $slug,
        ]);

        if ($created === false) {
            return null;
        }

        $id = (int) $this->db->getConnexion()->lastInsertId();
        return $this->findById($id);
    }

    public function updateTag(int $id, array $data): ?Tag {
        $setClauses = [];
        $params = ['id' => $id];

        if (array_key_exists('name', $data)) {
            $setClauses[] = 'name = :name';
            $params['name'] = $data['name'];
        }

        if (array_key_exists('slug', $data)) {
            $setClauses[] = 'slug = :slug';
            $params['slug'] = $data['slug'];
        }

        if (empty($setClauses)) {
            return $this->findById($id);
        }

        $query = 'UPDATE tags SET ' . implode(', ', $setClauses) . ' WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return $this->findById($id);
    }

    public function deleteTag(int $id): bool {
        $query = 'DELETE FROM tags WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);

        return $statement->execute(['id' => $id]);
    }
    public function findAllWithPublishedCount(): array {
        $query = 'SELECT t.id, t.name, t.slug, COUNT(dt.document_id) AS document_count
                  FROM tags t
                  LEFT JOIN document_tags dt ON dt.tag_id = t.id
                  LEFT JOIN documents d ON d.id = dt.document_id AND d.status = :status
                  GROUP BY t.id, t.name, t.slug
                  ORDER BY t.name ASC';
 
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['status' => 'published']);
 
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}

?>