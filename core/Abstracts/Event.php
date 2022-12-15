<?php


namespace MkyCore\Abstracts;


use MkyCore\Traits\Dispatcher;

abstract class Event implements \MkyCore\Interfaces\EventInterface
{

    use Dispatcher;

    /**
     * @var mixed
     */
    protected bool $propagationStopped = false;
    protected array $actions = [];
    protected array $params = [];
    protected mixed $target;


    /**
     * @param $flag
     */
    public function stopPropagation($flag)
    {
        $this->propagationStopped = $flag;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParam(string $key): mixed
    {
        return $this->params[$key] ?? $this->params;
    }

    /**
     * @return mixed
     */
    public function getTarget(): mixed
    {
        return $this->target;
    }
}