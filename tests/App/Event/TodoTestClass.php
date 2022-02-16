<?php


namespace MkyCore\Tests\App\Event;


class TodoTestClass
{
    private $name;
    private $completed;

    public function __construct($name, $completed)
    {
        $this->name = $name;
        $this->completed = $completed;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCompleted()
    {
        return $this->completed;
    }

    /**
     * @param bool $completed
     * @return TodoTestClass
     */
    public function setCompleted(bool $completed): TodoTestClass
    {
        $this->completed = $completed;
        return $this;
    }

    /**
     * @param mixed $name
     * @return TodoTestClass
     */
    public function setName($name): TodoTestClass
    {
        $this->name = $name;
        return $this;
    }
}