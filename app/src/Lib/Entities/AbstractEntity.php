<?php

namespace App\Lib\Entities;

abstract class AbstractEntity {

    abstract public function getId(): int | string;
    
    public function toArray(): array {
        $array = [];
        foreach ($this as $key => $value) {
            $array[$key] = $value;
        }
        return $array;
    }
}

?>
