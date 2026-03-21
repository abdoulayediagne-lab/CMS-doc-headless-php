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
}

?>