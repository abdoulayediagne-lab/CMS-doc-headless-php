<?php

namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;

class PageViewRepository extends AbstractRepository {
    public function getTable(): string {
        return 'page_views';
    }

    public function countViews(): int {
        $query = 'SELECT COUNT(*) FROM page_views';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function getTopViewedDocuments(int $limit = 5): array {
        $safeLimit = max(1, min(20, $limit));

        $query = 'SELECT d.id, d.title, d.slug, COUNT(pv.id) AS views
                  FROM page_views pv
                  INNER JOIN documents d ON d.id = pv.document_id
                  GROUP BY d.id, d.title, d.slug
                  ORDER BY views DESC, d.id ASC
                  LIMIT :limit';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->bindValue(':limit', $safeLimit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function recordView(int $documentId, ?string $ipAddress, ?string $userAgent, ?string $referer): void {
        $query = 'INSERT INTO page_views (document_id, ip_address, user_agent, referer)
                  VALUES (:document_id, :ip_address, :user_agent, :referer)';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute([
            'document_id' => $documentId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer' => $referer,
        ]);
    }
}

?>