<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class User extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;

    #[Column(type: 'varchar', size: 50)]
    public string $username;

    #[Column(type: 'varchar', size: 255)]
    public string $email;

    #[Column(type: 'varchar', size: 255)]
    public string $password_hash;

    #[Column(type: 'varchar', size: 20)]
    public string $role = 'reader';

    #[Column(type: 'boolean')]
    public bool $is_active = true;

    #[Column(type: 'timestamp')]
    public string $created_at;

    #[Column(type: 'timestamp')]
    public string $updated_at;

    public function getId(): int {
        return $this->id;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getRole(): string {
        return $this->role;
    }

    public function isActive(): bool {
        return $this->is_active;
    }

    public function setPassword(string $plainPassword): void {
        $this->password_hash = password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $plainPassword): bool {
        return password_verify($plainPassword, $this->password_hash);
    }
}

?>