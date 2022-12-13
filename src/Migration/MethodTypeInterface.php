<?php

namespace MkyCore\Migration;

use MkyCore\Exceptions\Migration\MethodTypeException;

interface MethodTypeInterface
{
    /**
     * @return string
     */
    public function getTable(): string;

    public function createRow(): string;

    public function id(bool $isInteger = true): static;

    public function primaryKey(): static;

    public function notNull(): static;

    public function unsigned(): static;

    public function integer(string $name): static;

    public function bigInt(string $name): static;

    public function smallInt(string $name): static;

    public function tinyInt(string $name): static;

    public function string(string $name, int $limit = 255): static;

    public function datetime(string $name): static;

    public function timestamps(): static;

    public function createAt(): static;

    /**
     * @param mixed|null $value
     * @return $this
     */
    public function default(mixed $value = null): static;

    public function timestamp(string $name): static;

    public function updateAt(): static;

    public function unique(): static;

    public function references(string $name, string $row = 'id'): static;

    public function cascade(): static;

    public function cascadeDelete(): static;

    public function cascadeUpdate(): static;

    public function noAction(): static;

    public function noActionDelete(): static;

    /**
     * @return $this
     */
    public function noActionUpdate(): static;

    /**
     * @param string $name
     * @return $this
     */
    public function float(string $name): static;

    public function text(string $name): static;

    public function autoIncrement(): static;

    public function dropColumnAndForeignKey(string $foreignKey): static;

    public function dropForeignKey(string $foreignKey): static;

    public function dropColumn(string $column): static;

    public function dropTable(): static;

    /**
     * @throws MethodTypeException
     */
    public function modify(string $column, string $type, array $options = []): static;

    /**
     * @throws MethodTypeException
     */
    public function rename(string $column, string $name, string $newType = null, array $options = []): static;

    public function foreignKey(string $name): static;
}