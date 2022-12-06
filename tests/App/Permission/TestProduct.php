<?php


namespace MkyCore\Tests\App\Permission;


use MkyCore\Model;

class TestProduct
{

    public function __construct(private readonly Model $user)
    {
    }

    public function getSeller(): Model
    {
        return $this->user;
    }

}