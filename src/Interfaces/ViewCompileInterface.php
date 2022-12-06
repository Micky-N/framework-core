<?php

namespace MkyCore\Interfaces;

interface ViewCompileInterface
{

    public function compile(string $view, array $params = []): string;
}