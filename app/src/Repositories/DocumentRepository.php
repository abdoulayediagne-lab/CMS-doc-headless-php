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

    public function findAllPaginated(int $limit = 20, int $offset = 0, ?string $status = null, ?int $sectionId = null, ?string $tagSlug = null, ?string $search = null): array {
        $query = 'SELECT DISTINCT d.*, u.username AS author_name, s.name AS section_name, s.slug AS section_slug FROM documents d LEFT JOIN users u ON u.id = d.author_id LEFT JOIN sections s ON s.id = d.section_id';
        $params = [];
        $whereClauses = [];

        if ($tagSlug !== null) {
            $query .= ' INNER JOIN document_tags dt ON dt.document_id = d.id INNER JOIN tags t ON t.id = dt.tag_id';
            $whereClauses[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $tagSlug;
        }

        if ($status !== null) {
            $whereClauses[] = 'd.status = :status';
            $params['status'] = $status;
        }

        if ($sectionId !== null) {
            $whereClauses[] = 'd.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($search !== null && $search !== '') {
            $whereClauses[] = '(d.title ILIKE :search OR d.content ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $query .= ' ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->getConnexion()->prepare($query);

        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findVisibleForEditor(int $editorId, int $limit = 20, int $offset = 0, ?string $status = null, ?int $sectionId = null, ?string $tagSlug = null, ?string $search = null): array {
        $query = 'SELECT DISTINCT d.*, u.username AS author_name, s.name AS section_name, s.slug AS section_slug FROM documents d LEFT JOIN users u ON u.id = d.author_id LEFT JOIN sections s ON s.id = d.section_id';
        $params = ['editor_id' => $editorId];
        $whereClauses = [];

        if ($tagSlug !== null) {
            $query .= ' INNER JOIN document_tags dt ON dt.document_id = d.id INNER JOIN tags t ON t.id = dt.tag_id';
            $whereClauses[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $tagSlug;
        }

        if ($status === null) {
            $whereClauses[] = '(d.status = :published_status OR d.author_id = :editor_id)';
            $params['published_status'] = 'published';
        } elseif ($status === 'published') {
            $whereClauses[] = 'd.status = :status';
            $params['status'] = 'published';
        } else {
            $whereClauses[] = 'd.status = :status';
            $whereClauses[] = 'd.author_id = :editor_id';
            $params['status'] = $status;
        }

        if ($sectionId !== null) {
            $whereClauses[] = 'd.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($search !== null && $search !== '') {
            $whereClauses[] = '(d.title ILIKE :search OR d.content ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $query .= ' ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->getConnexion()->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAll(?string $status = null, ?int $sectionId = null, ?string $tagSlug = null, ?string $search = null): int {
        $query = 'SELECT COUNT(DISTINCT d.id) FROM documents d';
        $params = [];
        $whereClauses = [];

        if ($tagSlug !== null) {
            $query .= ' INNER JOIN document_tags dt ON dt.document_id = d.id INNER JOIN tags t ON t.id = dt.tag_id';
            $whereClauses[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $tagSlug;
        }

        if ($status !== null) {
            $whereClauses[] = 'd.status = :status';
            $params['status'] = $status;
        }

        if ($sectionId !== null) {
            $whereClauses[] = 'd.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($search !== null && $search !== '') {
            $whereClauses[] = '(d.title ILIKE :search OR d.content ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    public function countVisibleForEditor(int $editorId, ?string $status = null, ?int $sectionId = null, ?string $tagSlug = null, ?string $search = null): int {
        $query = 'SELECT COUNT(DISTINCT d.id) FROM documents d';
        $params = ['editor_id' => $editorId];
        $whereClauses = [];

        if ($tagSlug !== null) {
            $query .= ' INNER JOIN document_tags dt ON dt.document_id = d.id INNER JOIN tags t ON t.id = dt.tag_id';
            $whereClauses[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $tagSlug;
        }

        if ($status === null) {
            $whereClauses[] = '(d.status = :published_status OR d.author_id = :editor_id)';
            $params['published_status'] = 'published';
        } elseif ($status === 'published') {
            $whereClauses[] = 'd.status = :status';
            $params['status'] = 'published';
        } else {
            $whereClauses[] = 'd.status = :status';
            $whereClauses[] = 'd.author_id = :editor_id';
            $params['status'] = $status;
        }

        if ($sectionId !== null) {
            $whereClauses[] = 'd.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($search !== null && $search !== '') {
            $whereClauses[] = '(d.title ILIKE :search OR d.content ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if (!empty($whereClauses)) {
            $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function findPublicPaginated(int $limit = 20, int $offset = 0, ?int $sectionId = null, ?string $tagSlug = null, ?string $search = null): array {
        $query = 'SELECT DISTINCT d.*, u.username AS author_name, s.name AS section_name, s.slug AS section_slug FROM documents d LEFT JOIN users u ON u.id = d.author_id LEFT JOIN sections s ON s.id = d.section_id';
        $params = ['status' => 'published'];
        $whereClauses = ['d.status = :status'];

        if ($tagSlug !== null) {
            $query .= ' INNER JOIN document_tags dt ON dt.document_id = d.id INNER JOIN tags t ON t.id = dt.tag_id';
            $whereClauses[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $tagSlug;
        }

        if ($sectionId !== null) {
            $whereClauses[] = 'd.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($search !== null && $search !== '') {
            $whereClauses[] = '(d.title ILIKE :search OR d.content ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $query .= ' WHERE ' . implode(' AND ', $whereClauses);
        $query .= ' ORDER BY d.published_at DESC NULLS LAST, d.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->getConnexion()->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $statement->execute();
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countPublic(?int $sectionId = null, ?string $tagSlug = null, ?string $search = null): int {
        $query = 'SELECT COUNT(DISTINCT d.id) FROM documents d';
        $params = ['status' => 'published'];
        $whereClauses = ['d.status = :status'];

        if ($tagSlug !== null) {
            $query .= ' INNER JOIN document_tags dt ON dt.document_id = d.id INNER JOIN tags t ON t.id = dt.tag_id';
            $whereClauses[] = 't.slug = :tag_slug';
            $params['tag_slug'] = $tagSlug;
        }

        if ($sectionId !== null) {
            $whereClauses[] = 'd.section_id = :section_id';
            $params['section_id'] = $sectionId;
        }

        if ($search !== null && $search !== '') {
            $whereClauses[] = '(d.title ILIKE :search OR d.content ILIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $query .= ' WHERE ' . implode(' AND ', $whereClauses);

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function findTagsForDocumentIds(array $documentIds): array {
        if (empty($documentIds)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($documentIds) as $index => $documentId) {
            $placeholder = ':doc_' . $index;
            $placeholders[] = $placeholder;
            $params['doc_' . $index] = (int) $documentId;
        }

        $query = 'SELECT dt.document_id, t.slug FROM document_tags dt INNER JOIN tags t ON t.id = dt.tag_id WHERE dt.document_id IN (' . implode(', ', $placeholders) . ') ORDER BY t.slug ASC';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $tagsByDocumentId = [];

        foreach ($rows as $row) {
            $docId = (int) $row['document_id'];
            if (!isset($tagsByDocumentId[$docId])) {
                $tagsByDocumentId[$docId] = [];
            }
            $tagsByDocumentId[$docId][] = $row['slug'];
        }

        return $tagsByDocumentId;
    }

    public function replaceDocumentTags(int $documentId, array $tagSlugs): void {
        $connection = $this->db->getConnexion();
        $connection->beginTransaction();

        try {
            $deleteStatement = $connection->prepare('DELETE FROM document_tags WHERE document_id = :document_id');
            $deleteStatement->execute(['document_id' => $documentId]);

            if (!empty($tagSlugs)) {
                $insertStatement = $connection->prepare('INSERT INTO document_tags (document_id, tag_id) VALUES (:document_id, :tag_id)');

                foreach ($tagSlugs as $tagSlug) {
                    $tagId = $this->findTagIdBySlug($tagSlug);
                    if ($tagId === null) {
                        $tagId = $this->createTagFromSlug($tagSlug);
                    }

                    $insertStatement->execute([
                        'document_id' => $documentId,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    private function findTagIdBySlug(string $slug): ?int {
        $statement = $this->db->getConnexion()->prepare('SELECT id FROM tags WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);

        $id = $statement->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    private function createTagFromSlug(string $slug): int {
        $name = str_replace('-', ' ', $slug);
        $name = ucwords($name);

        $statement = $this->db->getConnexion()->prepare('INSERT INTO tags (name, slug) VALUES (:name, :slug)');
        $statement->execute([
            'name' => $name,
            'slug' => $slug,
        ]);

        return (int) $this->db->getConnexion()->lastInsertId();
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