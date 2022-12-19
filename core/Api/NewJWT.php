<?php

namespace MkyCore\Api;

class NewJWT
{
    public function __construct(public readonly JsonWebToken $jsonWebToken, public readonly string $plainTextToken)
    {
    }
}