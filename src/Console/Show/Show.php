<?php

namespace MkyCore\Console\Show;

use MkyCore\Application;

class Show
{

    public function __construct(protected Application $app, protected readonly array $params = [], protected array $moduleOptions = [])
    {
    }
}