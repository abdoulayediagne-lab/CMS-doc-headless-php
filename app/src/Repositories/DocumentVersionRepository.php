<?php

namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;

class DocumentVersionRepository extends AbstractRepository {

    public function getTable(): string {
        return 'document_versions';
    }

    /**
     * Crée une nouvelle version d'un document
     */
    public function createVersion(int $documentId, int $authorId, string $title, string $content): int {
        $nextVersion = $this->getNextVersionNumber($documentId);

        $query = 'INSERT INTO document_versions (document_id, author_id, title, content, version_number) 
                  VALUES (:document_id, :author_id, :title, :content, :version_number)';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute([
            'document_id' => $documentId,
            'author_id' => $authorId,
            'title' => $title,
            'content' => $content,
            'version_number' => $nextVersion,
        ]);

        return $nextVersion;
    }

    /**
     * Récupère toutes les versions d'un document
     */
    public function findByDocumentId(int $documentId): array {
        $query = 'SELECT dv.*, u.username AS author_name 
                  FROM document_versions dv 
                  LEFT JOIN users u ON u.id = dv.author_id 
                  WHERE dv.document_id = :document_id 
                  ORDER BY dv.version_number DESC';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['document_id' => $documentId]);
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une version spécifique
     */
    public function findVersion(int $documentId, int $versionNumber): ?array {
        $query = 'SELECT dv.*, u.username AS author_name 
                  FROM document_versions dv 
                  LEFT JOIN users u ON u.id = dv.author_id 
                  WHERE dv.document_id = :document_id AND dv.version_number = :version_number 
                  LIMIT 1';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute([
            'document_id' => $documentId,
            'version_number' => $versionNumber,
        ]);

        $result = $statement->fetch(\PDO::FETCH_ASSOC);
        return $result === false ? null : $result;
    }

    /**
     * Récupère le prochain numéro de version
     */
    private function getNextVersionNumber(int $documentId): int {
        $query = 'SELECT COALESCE(MAX(version_number), 0) + 1 FROM document_versions WHERE document_id = :document_id';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['document_id' => $documentId]);
        return (int) $statement->fetchColumn();
    }
}

?>