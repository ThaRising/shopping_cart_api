<?php

namespace App\Schema;

use Symfony\Component\Validator\Constraints as Assert;
use App\Schema\AbstractSchema;

class ProductSchema extends AbstractSchema {
    /**
     * @Assert\NotNull
     * @Assert\Length(min=2, max=255)
     */
    protected string $name;

    /**
     * @Assert\NotNull(message="Okay Bruder")
     * @Assert\Length(min=1, max=12)
     */
    protected string $price;

    public function setName($value): void {
        $this->name = $value;
    }

    public function setPrice($value): void {
        $this->price = $value;
    }
}
