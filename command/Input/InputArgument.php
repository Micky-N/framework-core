<?php

namespace MkyCommand\Input;

class InputArgument
{
    const REQUIRED = 1;
    const OPTIONAL = 2;
    const IS_ARRAY = 4;

    private string|array|false|null $value = null;

    public function __construct(
        private readonly string $name,
        private readonly int $type,
        private readonly string $description
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
     * @return string|array|false|null
     */
    public function getValue(): string|array|false|null
    {
        return $this->value;
    }

    /**
     * @param string|array|false|null $value
     */
    public function setValue(string|array|false|null $value): void
    {
        $this->value = $value;
    }
}