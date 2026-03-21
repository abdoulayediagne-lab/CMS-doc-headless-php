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
}

?>