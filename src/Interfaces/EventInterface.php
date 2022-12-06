<?php

namespace MkyCore\Interfaces;


interface EventInterface
{

    /**
     * Set if propagation must be stopped
     *
     * @param bool $flag
     */
    public function stopPropagation(bool $flag);

    /**
     * Get if propagation is stopped
     * 
     * @return bool
     */
    public function isPropagationStopped(): bool;

    /**
     * Get event actions
     * 
     * @return array
     */
    public function getActions(): array;

    /**
     * Get event params
     *
     * @return array
     */
    public function getParams(): array;

    /**
     * Get event specific param
     *
     * @param string $key
     * @return array
     */
    public function getParam(string $key): mixed;

    /**
     * Get event target
     * 
     * @return mixed
     */
    public function getTarget(): mixed;
}
