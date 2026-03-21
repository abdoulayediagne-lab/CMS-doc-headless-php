<?php

namespace App\Repositories;

use App\Entities\Section;
use App\Lib\Repositories\AbstractRepository;

class SectionRepository extends AbstractRepository {
    public function getTable(): string {
        return 'sections';
    }

    public function findById(int $id): ?Section {
        $query = 'SELECT * FROM sections WHERE id = :id LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['id' => $id]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Section::class);

        $section = $statement->fetch();
        return $section === false ? null : $section;
    }

    public function findBySlug(string $slug): ?Section {
        $query = 'SELECT * FROM sections WHERE slug = :slug LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['slug' => $slug]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Section::class);

        $section = $statement->fetch();
        return $section === false ? null : $section;
    }

    public function findAllPaginated(int $limit = 50, int $offset = 0): array {
        $query = 'SELECT * FROM sections ORDER BY sort_order ASC, name ASC LIMIT :limit OFFSET :offset';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        $query = 'SELECT COUNT(*) FROM sections';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function create(array $data): ?Section {
        $query = 'INSERT INTO sections (parent_id, name, slug, description, sort_order) VALUES (:parent_id, :name, :slug, :description, :sort_order)';
        $statement = $this->db->getConnexion()->prepare($query);
        $created = $statement->execute([
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if ($created === false) {
            return null;
        }

        $id = (int) $this->db->getConnexion()->lastInsertId();
        return $this->findById($id);
    }

    public function updateSection(int $id, array $data): ?Section {
        $setClauses = [];
        $params = ['id' => $id];

        $allowedFields = ['parent_id', 'name', 'slug', 'description', 'sort_order'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = $field . ' = :' . $field;
                $params[$field] = $data[$field];
            }
        }

        if (empty($setClauses)) {
            return $this->findById($id);
        }

        $setClauses[] = 'updated_at = NOW()';
        $query = 'UPDATE sections SET ' . implode(', ', $setClauses) . ' WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return $this->findById($id);
    }

    public function deleteSection(int $id): bool {
        $query = 'DELETE FROM sections WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);

        return $statement->execute(['id' => $id]);
    }
}

?>