<?php

namespace MkyCore;

use MkyCore\Exceptions\Mysql\MysqlException;
use MkyCore\Traits\QueryMysql;
use Exception;
use ReflectionClass;
use ReflectionException;
use stdClass;

abstract class Model
{
    use QueryMysql;

    protected string $table;
    protected string $primaryKey = 'id';
    /**
     * Table field that can be set
     * @var array
     */
    protected array $settable = [];

    /**
     * Store date fields for automatic registration
     * ['creation' => creation column, 'update' => update column]
     *
     * @var array
     */
    protected array $dateTimes = [];

    /**
     * Get the model table name
     *
     * @return string
     * @throws MysqlException
     */
    public function getTable(): string
    {
        $class = get_called_class();
        if(empty($this->table)){
            $table = explode('\\', $class);
            $table = strtolower(end($table));
            if(get_plural($table)){
                return get_plural($table);
            }
            throw new MysqlException("Table $table doesn't exist");
        }
        return $this->table;
    }

    /**
     * Get the model primary key
     *
     * @return string|null
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey ?? null;
    }

    /**
     * Get the model current instance
     *
     * @return object
     * @throws ReflectionException
     */
    public static function getCurrentModel()
    {
        $current = new ReflectionClass(get_called_class());
        return $current->newInstance();
    }

    /**
     * Get a record
     *
     * @param mixed $id
     * @return $this|bool
     * @throws Exception
     */
    public static function find($id)
    {
        return self::where(
            self::getCurrentModel()->getPrimaryKey(),
            $id
        )->first();
    }

    /**
     * Get all records
     *
     * @return array
     */
    public static function all(): array
    {
        return self::get();
    }

    /**
     * Get the number of records
     *
     * @return int
     * @throws ReflectionException
     */
    public static function count(): int
    {
        $count = self::select(
            self::getCurrentModel()->getPrimaryKey() .
            ', COUNT(' .
            self::getCurrentModel()->getPrimaryKey() .
            ') AS count'
        )->map(self::getCurrentModel()->getPrimaryKey(), 'count');
        return (int)array_shift($count);
    }

    /**
     * Records a new data in table
     *
     * @param array $data
     * @param string $table
     * @return $this|bool
     * @throws ReflectionException
     */
    public static function create(array $data, string $table = '')
    {
        $data = self::setDatetime($data);
        $data = self::filterColumns($data);
        $table = $table ?: self::getCurrentModel()->getTable();
        $keys = [];
        $values = [];
        $inter = [];
        foreach ($data as $k => $v) {
            $keys[] = $k;
            $values[$k] = $v;
            $inter[] = ":$k";
        }
        $statement =
            'INSERT INTO ' .
            $table .
            ' (' .
            implode(', ', $keys) .
            ')';
        $statement .= ' VALUES (' . implode(', ', $inter) . ')';
        MysqlDatabase::prepare($statement, $values);
        return self::last();
    }

    /**
     * Records a new data in table
     *
     * @return $this|bool
     * @throws ReflectionException
     */
    public function save()
    {
        $modelArray = (array)$this;
        $modelArray = self::setDatetime($modelArray);
        $modelArray = self::filterColumns($modelArray);
        $keys = [];
        $values = [];
        $inter = [];
        foreach ($modelArray as $k => $v) {
            $keys[] = $k;
            $values[$k] = $v;
            $inter[] = ":$k";
        }
        $statement =
            'INSERT INTO ' .
            self::getCurrentModel()->getTable() .
            ' (' .
            implode(', ', $keys) .
            ')';
        $statement .= ' VALUES (' . implode(', ', $inter) . ')';
        MysqlDatabase::prepare($statement, $values);
        return self::last();
    }

    /**
     * Update a record
     *
     * @param $id
     * @param array $data
     * @return bool|$this
     * @throws ReflectionException
     */
    public static function update($id, array $data)
    {
        $keys = [];
        $values = [];
        $data = self::setUpdate($data);
        $data = self::filterColumns($data);
        foreach ($data as $k => $v) {
            $keys[] = "$k = :$k";
            $values[$k] = $v;
        }
        $values[self::getCurrentModel()->getPrimaryKey()] = $id;
        $statement =
            'UPDATE ' .
            self::getCurrentModel()->getTable() .
            ' SET ' .
            implode(', ', $keys) .
            ' WHERE ' .
            self::getCurrentModel()->getPrimaryKey() .
            ' = :' . self::getCurrentModel()->getPrimaryKey();
        MysqlDatabase::prepare($statement, $values);
        return self::find($id);
    }

    /**
     * Delete a record
     *
     * @param $id
     * @return array
     * @throws ReflectionException
     */
    public static function delete($id): array
    {
        $statement =
            'DELETE FROM ' .
            self::getCurrentModel()->getTable() .
            ' WHERE ' .
            self::getCurrentModel()->getPrimaryKey() .
            ' = :' . self::getCurrentModel()->getPrimaryKey();
        MysqlDatabase::prepare($statement, [self::getCurrentModel()->getPrimaryKey() => $id]);
        return self::all();
    }

    /**
     * Delete instance itself in database
     *
     * @return array
     * @throws ReflectionException
     */
    public function destroy(): array
    {
        $statement =
            'DELETE FROM ' .
            self::getCurrentModel()->getTable() .
            ' WHERE ' .
            self::getCurrentModel()->getPrimaryKey() .
            ' = :' . self::getCurrentModel()->getPrimaryKey();
        MysqlDatabase::prepare($statement, [self::getCurrentModel()->getPrimaryKey() => $this->{$this->primaryKey}]);
        return self::all();
    }

    /**
     * Get random key of table
     *
     * @return string
     * @throws ReflectionException
     */
    public static function shuffleId(): string
    {
        $pk = self::getCurrentModel()->getPrimaryKey();
        $ids = self::select($pk)->get();
        return $ids[array_rand($ids, 1)]->{$pk};
    }

    /**
     * Get all records of the one-to-many relation table
     *
     * @param string $model
     * @param string $foreignKey
     * @return array|bool|mixed
     * @throws ReflectionException
     * @throws MysqlException
     */
    protected function hasMany(string $model, string $foreignKey = '')
    {
        $table = (new $model())->getTable();
        $second = strtolower((new ReflectionClass($this))->getShortName());
        $foreignKey = $foreignKey ?: $second . '_' . $this->primaryKey;
        return MysqlDatabase::prepare(
            "
		SELECT {$table}.*
		FROM {$table}
		LEFT JOIN {$this->getTable()}
		ON {$table}.{$foreignKey} = 
            {$this->getTable()}.{$this->primaryKey} 
            WHERE {$this->getTable()}.{$this->primaryKey} = :{$this->primaryKey}",
            [$this->primaryKey => $this->{$this->primaryKey}],
            $model
        );
    }

    /**
     * Get record from the foreign table
     *
     * @param string $model
     * @param string $foreignKey
     * @return array|bool|mixed
     * @throws ReflectionException
     * @throws MysqlException
     * @example One to Many
     *
     */
    protected function belongsTo(string $model, string $foreignKey = '')
    {
        $table = new $model();
        $second = strtolower((new ReflectionClass($table))->getShortName());
        $foreignKey = $foreignKey ?: $second . '_' . $table->primaryKey;
        return MysqlDatabase::prepare(
            "
		SELECT {$table->getTable()}.*
		FROM {$table->getTable()}
		LEFT JOIN " .
            $this->getTable() .
            "
		ON " .
            $table->getTable() .
            '.' .
            $table->primaryKey .
            ' = ' .
            $this->getTable() .
            '.' .
            $foreignKey .
            "
		WHERE " .
            $this->getTable() .
            '.' .
            $this->primaryKey .
            " = :" . $this->primaryKey,
            [$this->primaryKey => $this->{$this->primaryKey}],
            $model,
            true
        );
    }

    /**
     * Get all records from the foreign table
     *
     * @param string $model
     * @param string $pivot
     * @param string $primaryKeyOne
     * @param string $primaryKeyTwo
     * @return array|bool|mixed
     * @throws ReflectionException
     * @example Many to Many
     */
    protected function belongsToMany(string $model, string $pivot = '', string $primaryKeyOne = '', string $primaryKeyTwo = '')
    {
        $first = strtolower((new ReflectionClass($this))->getShortName());
        $second = strtolower((new ReflectionClass($model))->getShortName());
        $secondInstance = new $model();
        $table = $secondInstance->getTable();
        $all = MysqlDatabase::query(
            'SHOW TABLES FROM ' . config('connections.mysql.name', 'database')
        );
        if(empty($pivot)){
            foreach ($all as $a) {
                if(strpos($a['Tables_in_' . config('connections.mysql.name', 'database')], '_') !== false){
                    $atest = explode('_', $a['Tables_in_' . config('connections.mysql.name', 'database')]);
                    if(in_array($first, $atest) && in_array($second, $atest)){
                        $pivot = $a['Tables_in_' . config('connections.mysql.name', 'database')];
                    }
                }
            }
        }

        $primaryKeyOne = $primaryKeyOne ?: $first . '_' . $this->getPrimaryKey();
        $primaryKeyTwo = $primaryKeyTwo ?: $second . '_' . $secondInstance->getPrimaryKey();
        return MysqlDatabase::prepare(
            "
		SELECT {$table}.*, {$pivot}.*
		FROM {$table}
		LEFT JOIN {$pivot}
		ON {$pivot}.{$primaryKeyTwo} = {$table}.{$secondInstance->getPrimaryKey()}
		WHERE {$pivot}.{$primaryKeyOne} = :$primaryKeyOne",
            ["$primaryKeyOne" => $this->{$this->getPrimaryKey()}],
            $model
        );
    }

    /**
     * Get record from the foreign table
     *
     * @param string $model
     * @param string $foreignKey
     * @return array|bool|mixed
     * @throws ReflectionException
     * @throws MysqlException
     * @example One to One
     *
     */
    protected function hasOne(string $model, string $foreignKey = '')
    {
        $table = (new $model())->getTable();
        $second = strtolower((new ReflectionClass($this))->getShortName());
        $foreignKey = $foreignKey ?: $second . '_' . $this->primaryKey;
        return MysqlDatabase::prepare(
            "
		SELECT {$table}.*
		FROM {$table}
		LEFT JOIN {$this->getTable()}
		ON {$table}.{$foreignKey} = " .
            $this->getTable() .
            '.' .
            $this->primaryKey .
            ' WHERE ' .
            $this->getTable() .
            '.' .
            $this->primaryKey .
            " = :{$this->primaryKey} LIMIT 1",
            ["{$this->primaryKey}" => $this->{$this->primaryKey}],
            $model,
            true
        );
    }

    /**
     * Get selected field from the relation table
     *
     * @param string $relation
     * @param array $properties
     * @return $this
     */
    public function with(string $relation, array $properties = [])
    {
        $instance = $this->{$relation};
        if(!is_array($instance)){
            if(!$properties){
                foreach ($instance as $key => $value) {
                    if(!in_array($key, [$instance->getPrimaryKey()])){
                        if(property_exists($this, $key)){
                            $this->{"{$relation}_{$key}"} = $value;
                        } else {
                            $this->{$key} = $value;
                        }
                    }
                }
            } else {
                foreach ($properties as $property) {
                    if(property_exists($this, $property)){
                        $this->{"{$relation}_{$property}"} = $instance->{$property};
                    } else {
                        $this->{$property} = $instance->{$property};
                    }
                }
            }
        } else {
            if($properties){
                foreach ($instance as $key => $model) {
                    $instance[$key] = new stdClass();
                    foreach ($properties as $property) {
                        $instance[$key]->{$property} = $model->{$property};
                    }
                }
            }
            $this->{$relation} = $instance;
        }
        return $this;
    }

    /**
     * Update record from current instance
     *
     * @param array $data
     * @return $this
     * @throws ReflectionException
     * @throws MysqlException
     *
     */
    public function modify(array $data = [])
    {
        $keys = [];
        $values = [];
        if(empty($data)){
            $data = (array)$this;
            unset($data[$this->getPrimaryKey()]);
        }
        $data = self::setUpdate($data);
        $data = self::filterColumns($data);
        foreach ($data as $k => $v) {
            $keys[] = "$k = :$k";
            $values[$k] = $v;
        }
        $values[$this->getPrimaryKey()] = $this->{$this->getPrimaryKey()};
        $statement =
            'UPDATE ' .
            $this->getTable() .
            ' SET ' .
            implode(', ', $keys) .
            ' WHERE ' .
            $this->getPrimaryKey() .
            ' = :' . $this->getPrimaryKey();
        MysqlDatabase::prepare($statement, $values);
        return $this;
    }

    /**
     * Update all relation table records
     *
     * @param string $relation
     * @param string $id
     * @param array $data
     * @return $this
     * @throws ReflectionException
     * @throws MysqlException
     * @example One to Many
     */
    public function modifyManyRelation(string $relation, string $id, array $data)
    {
        $instances = $this->{$relation};
        $instances = !empty($instances) ? (is_array($instances) ? $instances : [$instances]) : false;
        if($instances !== false){
            foreach ($instances as $instance) {
                $keys = [];
                $values = [];
                $data = self::setUpdate($data);
                $data = self::filterColumns($data);
                foreach ($data as $k => $v) {
                    $keys[] = "$k = :$k";
                    $values[$k] = $v;
                }
                $values[] = $instance->{$instance->getPrimaryKey()};
                $statement =
                    'UPDATE ' .
                    $instance->getTable() .
                    ' SET ' .
                    implode(', ', $keys) .
                    ' WHERE ' .
                    $instance->getPrimaryKey() .
                    ' = :' . $instance->getPrimaryKey();
                MysqlDatabase::prepare($statement, $values);
            }
            return $this;
        } else {
            throw new MysqlException(sprintf('No relationship with %s', $relation));
        }
    }

    /**
     * Create new record in the relation table
     *
     * @param $table
     * @param array $data
     * @return bool|$this
     * @throws ReflectionException
     */
    public function attach($table, array $data)
    {
        $data[$this->primaryKey] = $this->{$this->primaryKey};
        $data = self::setDatetime($data);
        $data = self::filterColumns($data);
        return self::create($data, $table);
    }

    /**
     * Get the name of table column
     *
     * @return array
     * @throws ReflectionException
     */
    private static function getColumns()
    {
        return array_map(function ($column) {
            return $column['Field'];
        }, MysqlDatabase::query("SHOW COLUMNS FROM " . self::getCurrentModel()->getTable()));
    }

    /**
     * Filter needed column
     *
     * @param array $data
     * @return array
     * @throws ReflectionException
     */
    private static function filterColumns(array $data)
    {
        $columns = self::getColumns();
        $filteredData = [];
        foreach ($columns as $key => $column) {
            $lowerColumn = strtolower($column);
            $filteredData[$column] = $data[$column] ?? $data[$lowerColumn] ?? null;
        }
        return array_filter($filteredData, function ($data) use ($filteredData) {
            if(!empty($filteredData[$data]) && !in_array($data, self::getCurrentModel()->settable)){
                throw new MysqlException(sprintf("Field %s is not settable", $data));
            }
            return $filteredData[$data];
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Put today date to dateTimes field
     *
     * @param array $data
     * @return array
     * @throws ReflectionException
     */
    private static function setDatetime(array $data): array
    {
        if(!empty(self::getCurrentModel()->dateTimes)){
            foreach (self::getCurrentModel()->dateTimes as $key => $datetime) {
                if(in_array($key, ['CREATED_AT', 'UPDATED_AT'])){
                    $data[$datetime] = date('Y-m-d H:i:s');
                }
            }
        }
        return $data;
    }

    /**
     * Put today date to update dateTimes field
     *
     * @param array $data
     * @return array
     * @throws ReflectionException
     */
    private static function setUpdate(array $data): array
    {
        if(!empty(self::getCurrentModel()->dateTimes) && isset(self::getCurrentModel()->dateTimes['UPDATED_AT'])){
            $data[self::getCurrentModel()->dateTimes['UPDATED_AT']] = date('Y-m-d H:i:s');
        }
        return $data;
    }

    public function __get($key)
    {
        if(empty($this->$key)){
            if(method_exists($this, 'get' . ucfirst($key))){
                return $this->{'get' . ucfirst($key)}();
            }
            return $this->$key();
        }
        return null;
    }
}
