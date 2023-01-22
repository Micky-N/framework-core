<?php

namespace MkyCommand\Input;

class InputOption
{
    const REQUIRED = 1;
    const OPTIONAL = 2;
    const IS_ARRAY = 4;
    const NONE = 8;
    const NEGATIVE = 16;

    private string|array|false|null $value = null;

    public function __construct(
        private readonly string  $name,
        private readonly ?string $shortname,
        private readonly string  $type,
        private readonly string  $description,
        private readonly mixed   $default = null
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
     * @return string
     */
    public function getType(): string
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
     * @return string
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * @return string|null
     */
    public function getShortname(): ?string
    {
        return $this->shortname;
    }

    /**
     * @return string|array|false|null
     */
    public function getValue(): string|array|false|null
    {
        return $this->value;
    }

    public function setValue(string|array|false|null $value): void
    {
        $this->value = $value;
    }

    public function hasDefault(): bool
    {
        return !is_null($this->default);
    }
}