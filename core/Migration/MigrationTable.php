<?php

namespace MkyCore\Migration;

class MigrationTable
{
    private array $columns = [];
    private string $primaryKey = 'id';

    public function __construct(private readonly string $table)
    {
    }

    /**
     * @param string $primaryKey
     */
    public function setPrimaryKey(string $primaryKey): void
    {
        $this->primaryKey = $primaryKey;
    }

    /**
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function id(string $primaryKey = 'id', bool $isInteger = true): ColumnType
    {
        if ($isInteger) {
            return $this->integer($primaryKey)->unsigned()->notNull()->primaryKey();
        }
        return $this->bigint($primaryKey)->unsigned()->notNull()->primaryKey();
    }

    public function integer(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'integer');
    }

    private function addColumn(string $column, string $type, ...$options): ColumnType
    {
        return new ColumnType($this->table, $column, $type, ...$options);
    }

    public function bigInt(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'bigInt');
    }

    public function smallInt(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'smallInt');
    }

    public function tinyInt(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'tinyInt');
    }

    public function string(string $column, int $limit = 255): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'string', $limit);
    }

    public function datetime(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'datetime');
    }
    
    public function dates(): ColumnType
    {
        $this->createAt();
        return $this->updateAt();
    }

    public function createAt(string $column = 'created_at'): ColumnType
    {
        return $this->timestamp($column)->notNull()->default('CURRENT_TIMESTAMP');
    }

    public function timestamp(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'timestamp');
    }

    public function updateAt(string $column = 'updated_at'): ColumnType
    {
        return $this->timestamp($column)->notNull()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function float(string $column, array $precision = []): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'float', $precision);
    }

    public function text(string $name): ColumnType
    {
        return $this->columns[] = $this->addColumn($name, 'text');
    }

    public function dropForeignKey(string $foreignKey): ColumnType
    {
        return $this->columns[] = $this->addColumn($foreignKey, 'dropForeignKey');
    }

    public function dropColumn(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'dropColumn');
    }

    public function dropTable(): ColumnType
    {
        return $this->columns[] = $this->addColumn($this->table, 'dropTable');
    }

    public function dropColumnAndForeignKey(string $foreignKey): ColumnType
    {
        return $this->columns[] = $this->addColumn($foreignKey, 'dropColumnAndForeignKey');
    }

    public function modify(string $column, string $type, array $options = []): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'modify', $type, $options);
    }

    public function rename(string $column, string $name, string $newType = null, array $options = []): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'rename', $name, $newType, $options);
    }

    public function foreignKey(string $column): ColumnType
    {
        return $this->columns[] = $this->addColumn($column, 'foreignKey');
    }

    public function getColumns(): array
    {
        return $this->columns;
    }
}