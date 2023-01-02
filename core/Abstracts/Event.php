<?php


namespace MkyCore\Abstracts;


use MkyCore\Interfaces\EventInterface;
use MkyCore\Traits\Dispatcher;

abstract class Event implements EventInterface
{

    use Dispatcher;

    protected bool $propagationStopped = false;
    protected array $actions = [];
    protected array $params = [];
    protected mixed $target;


    /**
     * Stop or continue propagation of event
     *
     * @param bool $flag
     */
    public function stopPropagation(bool $flag)
    {
        $this->propagationStopped = $flag;
    }

    /**
     * Check if event propagation is stopped
     *
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Get all event actions
     *
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get all event params
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get a event param
     *
     * @param string $key
     * @return mixed
     */
    public function getParam(string $key): mixed
    {
        return $this->params[$key] ?? $this->params;
    }

    /**
     * Get event target
     *
     * @return mixed
     */
    public function getTarget(): mixed
    {
        return $this->target;
    }
}