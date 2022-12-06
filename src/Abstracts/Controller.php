<?php

namespace MkyCore\Abstracts;

use MkyCore\Request;

abstract class Controller
{

    public function __construct(protected Request $request)
    {
    }
}