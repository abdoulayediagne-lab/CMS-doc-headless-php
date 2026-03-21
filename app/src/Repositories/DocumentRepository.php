<?php

namespace App\Repositories;

use App\Entities\Document;
use App\Lib\Repositories\AbstractRepository;

class DocumentRepository extends AbstractRepository {

    public function getTable(): string {
        return 'documents';
    }

    public function findById(int $id): ?Document {
        $query = 'SELECT * FROM documents WHERE id = :id LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['id' => $id]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Document::class);

        $doc = $statement->fetch();
        return $doc === false ? null : $doc;
    }

    public function findBySlug(string $slug): ?Document {
        $query = 'SELECT * FROM documents WHERE slug = :slug LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['slug' => $slug]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Document::class);

        $doc = $statement->fetch();
        return $doc === false ? null : $doc;
    }

    public function findAllPaginated(int $limit = 20, int $offset = 0, ?string $status = null): array {
        $query = 'SELECT d.*, u.username AS author_name FROM documents d LEFT JOIN users u ON u.id = d.author_id';
        $params = [];

        if ($status !== null) {
            $query .= ' WHERE d.status = :status';
            $params['status'] = $status;
        }

        $query .= ' ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->getConnexion()->prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, \PDO::PARAM_INT);

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAll(?string $status = null): int {
        $query = 'SELECT COUNT(*) FROM documents';
        $params = [];

        if ($status !== null) {
            $query .= ' WHERE status = :status';
            $params['status'] = $status;
        }

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public function create(array $data): ?Document {
        $query = 'INSERT INTO documents (section_id, author_id, title, slug, content, status, meta_title, meta_description, sort_order) 
                  VALUES (:section_id, :author_id, :title, :slug, :content, :status, :meta_title, :meta_description, :sort_order)';

        $statement = $this->db->getConnexion()->prepare($query);
        $created = $statement->execute([
            'section_id' => $data['section_id'] ?? null,
            'author_id' => $data['author_id'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'] ?? '',
            'status' => $data['status'] ?? 'draft',
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if ($created === false) {
            return null;
        }

        $id = (int) $this->db->getConnexion()->lastInsertId();
        return $this->findById($id);
    }

    public function updateDocument(int $id, array $data): ?Document {
        $setClauses = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'slug', 'content', 'status', 'section_id', 'meta_title', 'meta_description', 'sort_order', 'published_at'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($setClauses)) {
            return $this->findById($id);
        }

        $setClauses[] = "updated_at = NOW()";
        $query = 'UPDATE documents SET ' . implode(', ', $setClauses) . ' WHERE id = :id';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return $this->findById($id);
    }

    public function deleteDocument(int $id): bool {
        $query = 'DELETE FROM documents WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);
        return $statement->execute(['id' => $id]);
    }
}

?>