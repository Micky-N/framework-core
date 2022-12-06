<?php

namespace MkyCore\Validate;

use Closure;

class Rule
{


    public function __construct(protected ?Closure $callback, protected ?string $errorMessage, protected mixed $params = null)
    {
    }

    /**
     * Get callback value
     */ 
    public function getCallback(): Closure
    {
        return $this->callback;
    }

    /**
     * Get error message
     */ 
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @param Closure $callback
     */
    public function setCallback(Closure $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return string|null
     */
    public function getParam(): ?string
    {
        return $this->param;
    }

    /**
     * @param string|null $param
     */
    public function setParam(?string $param): void
    {
        $this->param = $param;
    }
}
