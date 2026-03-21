<?php

namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;

class AuditLogRepository extends AbstractRepository {
    public function getTable(): string {
        return 'audit_log';
    }

    public function logAction(
        int $userId,
        string $action,
        string $entityType,
        int $entityId,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $query = 'INSERT INTO audit_log (user_id, action, entity_type, entity_id, old_values, new_values) VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values)';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues !== null ? json_encode($oldValues) : null,
            'new_values' => $newValues !== null ? json_encode($newValues) : null,
        ]);
    }

    public function findPaginated(int $limit = 50, int $offset = 0): array {
        $query = 'SELECT a.*, u.username FROM audit_log a INNER JOIN users u ON u.id = a.user_id ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countAll(): int {
        $query = 'SELECT COUNT(*) FROM audit_log';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }
}

?>