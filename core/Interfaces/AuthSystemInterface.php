<?php

namespace MkyCore\Interfaces;

use MkyCore\Abstracts\Entity;

interface AuthSystemInterface
{

    /**
     * Check if password is valid
     *
     * @param array $credentials
     * @return Entity|false
     */
    public function passwordCheck(array $credentials): Entity|false;
}