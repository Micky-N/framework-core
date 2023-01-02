<?php

namespace MkyCore\Abstracts;

use MkyCore\Application;
use MkyCore\Request;

abstract class Controller
{

    protected ?ModuleKernel $kernel;

    public function __construct(protected Request $request, protected Application $app)
    {
        $this->kernel = $this->app->getModuleKernel($this->app->getCurrentRoute()->getModule());
    }
    
}