<?php

namespace MkyCommand\Input;

class InputArgument
{
    const REQUIRED = 1;
    const OPTIONAL = 2;
    const ARRAY = 4;

    private mixed $value = null;

    public function __construct(
        private readonly string $name,
        private readonly int    $type,
        private readonly string $description = ''
    )
    {

    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}