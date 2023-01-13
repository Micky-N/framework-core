<?php

namespace MkyCore\Migration;

class MigrationTable
{
    private array $columns = [];

    public function __construct(private readonly string $table)
    {
    }


    /**
     * Make a integer or big integer primary key column
     *
     * @param string $primaryKey
     * @param bool $isInteger
     * @return ColumnType
     */
    public function id(string $primaryKey = 'id', bool $isInteger = true): ColumnType
    {
        if ($isInteger) {
            return $this->integer($primaryKey)->unsigned()->notNull()->primaryKey();
        }
        return $this->bigint($primaryKey)->unsigned()->notNull()->primaryKey();
    }

    /**
     * Make an integer column
     *
     * @param string $column
     * @return ColumnType
     */
    public function integer(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'integer');
    }

    /**
     * Add a new column
     *
     * @param string $column
     * @param string $type
     * @param ...$options
     * @return ColumnType
     */
    private function addColumn(string $column, string $type, ...$options): ColumnType
    {
        return new ColumnType($this->table, $column, $type, ...$options);
    }

    /**
     * Make a big integer column
     *
     * @param string $column
     * @return ColumnType
     */
    public function bigInt(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'bigInt');
    }

    /**
     * Make a small integer column
     *
     * @param string $column
     * @return ColumnType
     */
    public function smallInt(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'smallInt');
    }

    /**
     * Make a tiny integer column
     *
     * @param string $column
     * @return ColumnType
     */
    public function tinyInt(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'tinyInt');
    }

    /**
     * Make a boolean column
     *
     * @param string $column
     * @return ColumnType
     */
    public function boolean(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'tinyInt');
    }

    /**
     * Make a varchar column
     *
     * @param string $column
     * @param int $limit
     * @return ColumnType
     */
    public function string(string $column, int $limit = 255): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'string', $limit);
    }

    /**
     * Make a datetime column
     *
     * @param string $column
     * @return ColumnType
     */
    public function datetime(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'datetime');
    }

    /**
     * Set created_at and updated_at column
     *
     * @return ColumnType
     */
    public function dates(): ColumnType
    {
        $this->createdAt();
        return $this->updatedAt();
    }

    /**
     * Make a created_at timestamp column
     *
     * @param string $column
     * @return ColumnType
     */
    public function createdAt(string $column = 'created_at'): ColumnType
    {
        return $this->timestamp($column)->notNull()->default('CURRENT_TIMESTAMP');
    }

    /**
     * Make a timestamp column
     *
     * @param string $column
     * @return ColumnType
     */
    public function timestamp(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'timestamp');
    }

    /**
     * Make a updated_at timestamp column
     *
     * @param string $column
     * @return ColumnType
     */
    public function updatedAt(string $column = 'updated_at'): ColumnType
    {
        return $this->timestamp($column)->notNull()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    /**
     * Make a float column
     *
     * @param string $column
     * @param array $precision
     * @return ColumnType
     */
    public function float(string $column, array $precision = []): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'float', $precision);
    }

    /**
     * Make a text type column
     *
     * @param string $column
     * @return ColumnType
     */
    public function text(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'text');
    }

    /**
     * Drop foreign key
     *
     * @param string $foreignKey
     * @return ColumnType
     */
    public function dropForeignKey(string $foreignKey): ColumnType
    {
        return $this->columns[] = $this->addColumn($foreignKey, 'dropForeignKey');
    }

    /**
     * Drop column
     *
     * @param string $column
     * @return ColumnType
     */
    public function dropColumn(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'dropColumn');
    }

    /**
     * Drop column and foreign keys linked to
     *
     * @param string $foreignKeythis method will not need a second parameter
     * @param string|null $constraint
     * @return ColumnType
     */
    public function dropColumnAndForeignKey(string $foreignKey, string $constraint = null): ColumnType
    {
        return $this->columns[] = $this->addColumn($foreignKey, 'dropColumnAndForeignKey', $constraint);
    }

    /**
     * Modify column type
     *
     * @param string $column
     * @param string $type
     * @param mixed ...$options
     * @return ColumnType
     */
    public function modify(string $column, string $type, ...$options): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'modify', $type, ...$options);
    }

    /**
     * Rename column, change type optionally
     *
     * @param string $column
     * @param string $name
     * @param string|null $newType
     * @param mixed ...$options
     * @return ColumnType
     */
    public function rename(string $column, string $name, string $newType = null, ... $options): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'rename', $name, $newType, ...$options);
    }

    /**
     * Set column as a foreign key column
     *
     * @param string $column
     * @param string|null $constraint
     * @return ColumnType
     */
    public function foreignKey(string $column, string $constraint = null): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'foreignKey', $constraint);
    }

    /**
     * Get columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }
}