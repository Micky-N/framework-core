<?php

namespace MkyCore\Interfaces;

use MkyCore\Abstracts\Entity;

interface AuthSystemInterface
{

    public function passwordCheck(array $credentials): Entity|bool;
}