<?php

namespace App\Repositories;

use App\Entities\User;
use App\Lib\Repositories\AbstractRepository;

class UserRepository extends AbstractRepository {
    public function getTable(): string {
        return 'users';
    }

    public function findByEmail(string $email): ?User {
        $query = 'SELECT * FROM users WHERE email = :email LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['email' => $email]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, User::class);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function findById(int $id): ?User {
        $query = 'SELECT * FROM users WHERE id = :id LIMIT 1';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute(['id' => $id]);
        $statement->setFetchMode(\PDO::FETCH_CLASS, User::class);

        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public function create(string $username, string $email, string $plainPassword): ?User {
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

        $query = 'INSERT INTO users (username, email, password_hash, role, is_active) VALUES (:username, :email, :password_hash, :role, :is_active)';
        $statement = $this->db->getConnexion()->prepare($query);
        $created = $statement->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => 'reader',
            'is_active' => true,
        ]);

        if ($created === false) {
            return null;
        }

        return $this->findByEmail($email);
    }

    public function countUsers(): int {
        $query = 'SELECT COUNT(*) FROM users';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute();

        return (int) $statement->fetchColumn();
    }

    public function findAllPaginated(int $limit = 50, int $offset = 0): array {
        $query = 'SELECT id, username, email, role, is_active, created_at, updated_at FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateAdminFields(int $id, ?string $role = null, ?bool $isActive = null): ?User {
        $setClauses = [];
        $params = ['id' => $id];

        if ($role !== null) {
            $setClauses[] = 'role = :role';
            $params['role'] = $role;
        }

        if ($isActive !== null) {
            $setClauses[] = 'is_active = :is_active';
            $params['is_active'] = $isActive;
        }

        if (empty($setClauses)) {
            return $this->findById($id);
        }

        $setClauses[] = 'updated_at = NOW()';
        $query = 'UPDATE users SET ' . implode(', ', $setClauses) . ' WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute($params);

        return $this->findById($id);
    }

    public function deleteById(int $id): bool {
        $query = 'DELETE FROM users WHERE id = :id';
        $statement = $this->db->getConnexion()->prepare($query);

        try {
            $statement->execute(['id' => $id]);
        } catch (\PDOException $exception) {
            return false;
        }

        return $statement->rowCount() > 0;
    }

    public function anonymizeById(int $id): ?User {
        $suffix = time() . '_' . $id;
        $anonymizedUsername = 'deleted_user_' . $suffix;
        $anonymizedEmail = 'deleted+' . $suffix . '@example.invalid';
        $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $query = 'UPDATE users
                  SET username = :username,
                      email = :email,
                      password_hash = :password_hash,
                      role = :role,
                      is_active = :is_active,
                      updated_at = NOW()
                  WHERE id = :id';

        $statement = $this->db->getConnexion()->prepare($query);
        $statement->execute([
            'id' => $id,
            'username' => $anonymizedUsername,
            'email' => $anonymizedEmail,
            'password_hash' => $passwordHash,
            'role' => 'reader',
            'is_active' => false,
        ]);

        if ($statement->rowCount() === 0) {
            return null;
        }

        return $this->findById($id);
    }
}

?>