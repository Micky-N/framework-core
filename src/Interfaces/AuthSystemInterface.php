<?php

namespace MkyCore\Interfaces;

interface AuthSystemInterface
{

    public function passwordCheck(array $credentials): static|bool;
}