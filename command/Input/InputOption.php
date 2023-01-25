<?php

namespace MkyCommand\Input;

class InputOption
{
    const REQUIRED = 1;
    const OPTIONAL = 2;
    const ARRAY = 4;
    const NONE = 8;
    const NEGATIVE = 16;

    private mixed $value = null;

    public function __construct(
        private readonly string  $name,
        private readonly ?string $shortname,
        private readonly int     $type,
        private readonly string  $description = '',
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
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function hasDefault(): bool
    {
        return !is_null($this->default);
    }

    public function hasShortName(): bool
    {
        return !is_null($this->shortname);
    }
}