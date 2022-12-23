<?php

namespace MkyCore\Api;

class NewJsonWebToken
{
    public function __construct(public readonly JsonWebToken $jsonWebToken, public readonly string $plainTextToken)
    {
    }
}