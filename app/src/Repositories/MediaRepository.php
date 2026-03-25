<?php

namespace App\Repositories;

use App\Entities\Media;
use App\Lib\Repositories\AbstractRepository;

class MediaRepository extends AbstractRepository {
    public function getTable(): string {
        return 'media';
    }

    public function findById(int $id): ?Media {
        $query = 'SELECT * FROM media WHERE id = :id LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['id' => $id]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, Media::class);

        $media = $statement->fetch();
        return $media === false ? null : $media;
    }

    public function findPaginated(int $limit = 20, int $offset = 0, ?int $documentId = null, ?string $search = null): array {
        $where = [];
        $params = [];

        if ($documentId !== null) {
            $where[] = 'm.document_id = :document_id';
            $params['document_id'] = $documentId;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(LOWER(m.filename) LIKE :search OR LOWER(COALESCE(m.alt_text, '')) LIKE :search)";
            $params['search'] = '%' . strtolower($search) . '%';
        }

        $query = 'SELECT m.*, u.username AS uploaded_by_name FROM media m INNER JOIN users u ON u.id = m.uploaded_by';
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }
        $query .= ' ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->getConnexion()->prepare($query);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAll(?int $documentId = null, ?string $search = null): int {
        $where = [];
        $params = [];

        if ($documentId !== null) {
            $where[] = 'document_id = :document_id';
            $params['document_id'] = $documentId;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(LOWER(filename) LIKE :search OR LOWER(COALESCE(alt_text, '')) LIKE :search)";
            $params['search'] = '%' . strtolower($search) . '%';
        }

        $query = 'SELECT COUNT(*) FROM media';
        if (!empty($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function create(array $data): ?Media {
        $query = 'INSERT INTO media (document_id, uploaded_by, filename, alt_text, mime_type, file_size, path) 
                  VALUES (:document_id, :uploaded_by, :filename, :alt_text, :mime_type, :file_size, :path)';
        $statement = $this->db->getConnexion()->prepare($query);
        $created = $statement->execute([
            'document_id' => $data['document_id'] ?? null,
            'uploaded_by' => $data['uploaded_by'],
            'filename' => $data['filename'],
            'alt_text' => $data['alt_text'] ?? null,
            'mime_type' => $data['mime_type'],
            'file_size' => $data['file_size'],
            'path' => $data['path'],
        ]);

        if ($created === false) {
            return null;
        }

        $id = (int) $this->db->getConnexion()->lastInsertId();
        return $this->findById($id);
    }

    public function updateMedia(int $id, array $data): ?Media {
        $setClauses = [];
        $params = ['id' => $id];

        if (array_key_exists('alt_text', $data)) {
            $setClauses[] = 'alt_text = :alt_text';
            $params['alt_text'] = $data['alt_text'];
        }

        if (array_key_exists('document_id', $data)) {
            $setClauses[] = 'document_id = :document_id';
            $params['document_id'] = $data['document_id'];
        }

        if (empty($setClauses)) {
            return $this->findById($id);
        }

        $query = 'UPDATE media SET ' . implode(', ', $setClauses) . ' WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return $this->findById($id);
    }

    public function deleteMedia(int $id): bool {
        $query = 'DELETE FROM media WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);
        return $statement->execute(['id' => $id]);
    }
}

?>