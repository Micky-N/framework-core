<?php

namespace MkyCore\Migration;

use MkyCore\Exceptions\Migration\MethodTypeException;
use MkyCore\Exceptions\Migration\MigrationException;
use MkyCore\Facades\DB;
use ReflectionClass;
use ReflectionMethod;

class ColumnType
{

    private string $query = '';

    public function __construct(private string $table, string $column, string $type, ...$options)
    {
        $this->{$type}($column, ...$options);
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    public function createRow(): string
    {
        return preg_replace('/\s+/', ' ', trim($this->query));
    }

    public function primaryKey(): static
    {
        $this->query .= "PRIMARY KEY";
        return $this;
    }

    public function notNull(): static
    {
        $this->query .= " NOT NULL ";
        return $this;
    }

    public function unsigned(): static
    {
        $this->query .= ' unsigned ';
        return $this;
    }

    public function integer(string $name): static
    {
        $this->query = "`$name` INT";
        return $this;
    }

    public function bigInt(string $name): static
    {
        $this->query = "`$name` BIGINT";
        return $this;
    }

    public function smallInt(string $name): static
    {
        $this->query = "`$name` SMALLINT";
        return $this;
    }

    public function tinyInt(string $name): static
    {
        $this->query = "`$name` TINYINT";
        return $this;
    }

    public function string(string $name, int $limit = 255): static
    {
        $this->query = "`$name` varchar(" . $limit . ")";
        return $this;
    }

    public function datetime(string $name): static
    {
        $this->query = "`$name` datetime";
        return $this;
    }

    /**
     * @param mixed|null $value
     * @return $this
     */
    public function default(mixed $value = null): static
    {
        $this->query .= "DEFAULT " . $value;
        return $this;
    }

    public function timestamp(string $name): static
    {
        $this->query = "`$name` timestamp";
        return $this;
    }

    public function unique(): static
    {
        $this->query .= ' UNIQUE ';
        return $this;
    }

    public function references(string $name, string $row = 'id'): static
    {
        $this->query .= " REFERENCES `$name` (`$row`)";
        return $this;
    }

    public function cascade(): static
    {
        $this->cascadeDelete();
        $this->cascadeUpdate();
        return $this;
    }

    public function cascadeDelete(): static
    {
        $this->query .= " ON DELETE CASCADE ";
        return $this;
    }

    public function cascadeUpdate(): static
    {
        $this->query .= " ON UPDATE CASCADE ";
        return $this;
    }

    public function noAction(): static
    {
        $this->noActionDelete();
        $this->noActionUpdate();
        return $this;
    }

    public function noActionDelete(): static
    {
        $this->query .= " ON DELETE NO ACTION ";
        return $this;
    }

    /**
     * @return $this
     */
    public function noActionUpdate(): static
    {
        $this->query .= " ON UPDATE NO ACTION ";
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function float(string $name): static
    {
        $this->query = "`$name` FLOAT";
        return $this;
    }

    public function text(string $name): static
    {
        $this->query = "`$name` TEXT";
        return $this;
    }

    public function autoIncrement(): static
    {
        $this->query .= " AUTO_INCREMENT ";
        return $this;
    }

    public function dropColumnAndForeignKey(string $foreignKey): static
    {
        $foreignKeysDb = $this->getForeignKeysDb($foreignKey);
        $queries = [];
        for ($i = 0; $i < count($foreignKeysDb); $i++) {
            $fkDb = $foreignKeysDb[$i];
            $this->dropForeignKey($fkDb);
            $queries[] = $this->query;
        }
        $this->dropColumn($foreignKey);
        $queries[] = $this->query;
        $this->query = join(", ", $queries);
        return $this;
    }

    private function getForeignKeysDb(string $foreignKey): array
    {
        $FKs = DB::prepare("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
WHERE information_schema.TABLE_CONSTRAINTS.CONSTRAINT_TYPE = 'FOREIGN KEY' 
AND information_schema.TABLE_CONSTRAINTS.TABLE_SCHEMA = :schema
AND information_schema.TABLE_CONSTRAINTS.TABLE_NAME = :table
AND CONSTRAINT_NAME LIKE :fk", ['table' => $this->table, 'schema' => DB::getDatabase(), 'fk' => "FK_{$foreignKey}%"]);

        return array_map(function (array $fk) {
            return array_shift($fk);
        }, $FKs);
    }

    public function dropForeignKey(string $foreignKey): static
    {
        $this->query = "DROP FOREIGN KEY $foreignKey";
        return $this;
    }

    public function dropColumn(string $column): static
    {
        $this->query = "DROP COLUMN `$column`";
        return $this;
    }

    public function dropTable(): static
    {
        $this->query = "DROP TABLE IF EXISTS `$this->table`";
        return $this;
    }

    /**
     * @throws MethodTypeException
     */
    public function modify(string $column, string $type, array $options = []): static
    {
        $this->useMethod($type, $column, $options);
        $query = 'MODIFY ';
        $this->query = $query . $this->query;
        return $this;
    }

    /**
     * @param string $method
     * @param string $column
     * @param array $options
     * @return mixed
     * @throws MethodTypeException
     */
    private function useMethod(string $method, string $column, array $options = []): mixed
    {
        $reflectionClass = new ReflectionClass($this);
        $methods = array_map(fn(ReflectionMethod $meth) => $meth->getName(), $reflectionClass->getMethods());
        if (!in_array($method, $methods)) {
            throw new MethodTypeException("Method $method not found or implement");
        }
        return $this->{$method}($column, ...$options);
    }

    /**
     * @throws MethodTypeException
     */
    public function rename(string $column, string $name, string $newType = null, array $options = []): static
    {
        $type = '';
        if (!$newType) {
            $res = $this->getColumnType($column);
            $res = $res ?: 'varchar(255)';
            $type = " $res";
            
        } else {
            $type = $this->useMethod($newType, $column, $options);
            $type = str_replace("`$column`", '', $type->getQuery());
            $type = " ".trim($type);
        }
        $this->query = "CHANGE `$column` `$name`$type";
        return $this;
    }

    private function getColumnType(string $column): string|bool
    {
        $res = DB::prepare("
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = :schema
AND TABLE_NAME = :table 
AND COLUMN_NAME = :column", ['table' => $this->table, 'column' => $column, 'schema' => DB::getDatabase()], null, true);
        return $res ? array_shift($res) : $res;
    }

    public function foreignKey(string $name): static
    {
        $fk = "FK_{$name}_" . rand();
        $constrain = "CONSTRAINT `$fk`";
        $foreignKey = "FOREIGN KEY (`$name`)";
        $query = $this->query;
        $this->query = "$constrain $foreignKey";
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}