<?php

namespace MkyCore\Validate;

use Closure;

class Rule
{

    private Closure $callback;
    private string $errorMessage;

    public function __construct(Closure $callback, string $errorMessage)
    {
        $this->callback = $callback;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Get callback value
     */ 
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Get error message
     */ 
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}
