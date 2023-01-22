<?php

namespace MkyCore\Tests\Entities;

use MkyCore\Abstracts\Entity;

class UserTest extends Entity
{

    public function __construct(public int $id = 7, public string $name = 'Micky')
    {
    }
}