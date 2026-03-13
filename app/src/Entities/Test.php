<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Entities\AbstractEntity;

class Test {

    #[Id]
    #[Column(type: 'varchar', size: 255)]
    public string $email;

    #[Column(type: 'varchar', size: 255)]
    public string $username;
    
    #[Column(type: 'varchar', size: 255)]
    public string $password;
    
    #[Column(type: 'int', size: 9, nullable: true)]
    public int|null $age;
    

    public function getEmail(): string {
        return $this->email;
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function setUserame(string $username): void {
        $this->username = $username;
    }
}

?>
