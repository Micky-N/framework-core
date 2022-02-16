<?php


namespace MkyCore\Tests\App\Permission;


class TestProduct
{

    /**
     * @var mixed
     */
    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getSeller()
    {
        return $this->user;
    }

}