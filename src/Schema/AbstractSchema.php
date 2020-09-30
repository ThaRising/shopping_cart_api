<?php

namespace App\Schema;

abstract class AbstractSchema {
    public function get_fields(): array {
        $class_vars = get_class_vars(get_class($this));
        return array_keys($class_vars);
    }

    public function get_object_vars(): array {
        return get_object_vars($this);
    }
}
