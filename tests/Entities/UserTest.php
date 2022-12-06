<?php

namespace MkyCore\Tests\Entities;

use MkyCore\Abstracts\Entity;
use MkyCore\Traits\Notify;

class UserTest extends Entity
{
    use Notify;

    public function __construct(public int $id = 7, public string $name = 'Micky')
    {
    }
}