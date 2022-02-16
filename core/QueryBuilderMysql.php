<?php

namespace MkyCore;

use MkyCore\MysqlDatabase;
use Exception;
use stdClass;

class QueryBuilderMysql
{
    /**
     * SELECT
     * @var array
     */
    private array $fields = [];

    /**
     * FROM
     * @var array
     */
    private array $from = [];

    /**
     * WHERE
     * @var array
     */
    private array $conditions = [];

    /**
     * ORDER BY
     * @var array
     */
    private array $order = [];

    /**
     * LIMIT
     * @var array
     */
    private array $limit = [];

    /**
     * JOIN ON
     * @var array
     */
    private array $joins = [];

    /**
     * GROUP BY
     * @var array
     */
    private array $group = [];

    private Model $instance;

    public function __construct($instance)
    {
        $this->instance = new $instance();
        return $this;
    }

    /**
     * @see MysqlDatabase::query()
     * @param string $statement
     * @return array
     * @throws Exception
     */
    public function query(string $statement): array
    {
        return MysqlDatabase::query($statement);
    }

    /**
     * @see MysqlDatabase::prepare()
     * @param string $statement
     * @param array $attribute
     * @return array
     * @throws Exception
     */
    public function prepare(string $statement, array $attribute): array
    {
        return MysqlDatabase::prepare($statement, $attribute);
    }

    /**
     * @return $this
     */
    public function select()
    {
        $this->fields = func_get_args();
        return $this;
    }

    /**
     * @param string $table
     * @param null $alias
     * @return $this
     */
    public function from(string $table, $alias = null): QueryBuilderMysql
    {
        if (is_null($alias)) {
            $this->from[] = "$table";
        } else {
            $this->from[] = "$table AS $alias";
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function where(): QueryBuilderMysql
    {
        if (count(func_get_args()) === 2)
            $this->conditions[] = sprintf('%s = "%s"', ...func_get_args());
        else if (count(func_get_args()) === 3)
            $this->conditions[] = sprintf('%s %s "%s"', ...func_get_args());
        return $this;
    }

    /**
     * @return $this
     */
    public function orderBy(): QueryBuilderMysql
    {
        foreach (func_get_args() as $arg) {
            if (count(explode(' ', $arg)) == 1) {
                $arg .= ' ASC';
            }
            $this->order[] = $arg;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function limit(): QueryBuilderMysql
    {
        if (count(func_get_args()) === 2)
            $this->limit[] = join(' OFFSET ', func_get_args());
        else if (count(func_get_args()) === 1)
            $this->limit[] = join(' ', func_get_args());
        return $this;
    }

    /**
     * @return $this
     */
    public function groupBy(): QueryBuilderMysql
    {
        $this->group[] = join(', ', func_get_args());
        return $this;
    }

    /**
     * @param string $join_table
     * @param string $on
     * @param string $operation
     * @param string $to
     * @param string $aliasFirstTable
     * @return $this
     * @throws Exception
     */
    public function join(string $join_table, string $on, string $operation, string $to, string $aliasFirstTable = ''): QueryBuilderMysql
    {
        if (!strpos($on, '.')) {
            $on = empty($alias) ? $this->instance->getTable() . ".$on" : "$aliasFirstTable.$on";
        }
        if (!strpos($to, '.')) {
            $to = "$join_table.$to";
        }
        $this->joins[] = [$join_table, $on, $operation, $to];

        return $this;
    }

    /**
     * Make array with selected field
     *
     * @param string $key
     * @param mixed $value
     * @return array
     * @throws Exception
     */
    public function map(string $key = '', $value = null): array
    {
        $query = MysqlDatabase::query($this->stringify());
        $value = str_replace(' ', '', $value);
        $valuemap = !empty($value) ? $this->mapping($value, $query) : $query;
        if ($key) {
            $keymap = $key ? $this->mapping($key, $query) : range(1, count($query));
            $valuemap = array_combine($keymap, $valuemap);
        }
        return $valuemap;
    }

    /**
     * Get all key column data
     *
     * @param mixed $key
     * @param array $query
     * @return array
     */
    private function mapping($key, array $query = []): array
    {
        return array_map(function ($km) use ($key) {
            if (is_string($key)) {
                return $km[$key];
            }
            $map = [];
            foreach ($key as $k => $v) {
                $map[$v] = $km[$v];
            }
            return $map;
        }, $query);
    }

    /**
     * @return string
     */
    private function hasFields()
    {
        if (!empty($this->fields)) {
            return 'SELECT ' . implode(', ', $this->fields);
        } else {
            return 'SELECT *';
        }
    }

    /**
     * @return string
     */
    private function hasLimit()
    {
        if (!empty($this->limit))
            return ' LIMIT ' . implode(' ', $this->limit);
        else
            return '';
    }

    /**
     * @return string
     */
    private function hasConditions()
    {
        if (!empty($this->conditions))
            return ' WHERE ' . implode(' AND ', $this->conditions);
        else
            return '';
    }

    /**
     * @return string
     */
    private function hasOrder()
    {
        if (!empty($this->order))
            return ' ORDER BY ' . implode(', ', $this->order);
        else
            return '';
    }

    /**
     * @return string
     * @throws Exception
     */
    private function hasFrom()
    {
        if (!empty($this->from)) {
            return ' FROM ' . implode(', ', $this->from);
        } else {
            return ' FROM ' . $this->instance->getTable();
        }
    }

    /**
     * @return string
     */
    private function hasJoin()
    {
        $syntax = '';
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $syntax .= sprintf(" LEFT JOIN %s ON %s %s %s", ...$join);
            }
        }
        return $syntax;
    }

    /**
     * @return string
     */
    private function hasGroup()
    {
        if (!empty($this->group))
            return ' GROUP BY ' . implode(', ', $this->group);
        else
            return '';
    }

    /**
     * Get all records
     *
     * @return array|bool
     * @throws Exception
     */
    public function get()
    {
        return MysqlDatabase::query($this->stringify(), get_class($this->instance));
    }

    /**
     * Get all records as array
     *
     * @return array|bool
     * @throws Exception
     */
    public function toArray()
    {
        return MysqlDatabase::query($this->stringify());
    }

    /**
     * Get the first record
     *
     * @return Model|bool
     * @throws Exception
     */
    public function first()
    {
        $this->limit(1);
        return MysqlDatabase::query($this->stringify(), get_class($this->instance), true);
    }

    /**
     * Get the last record
     *
     * @return Model|bool
     * @throws Exception
     */
    public function last()
    {
        $this->limit(1);
        $this->orderBy($this->instance->getPrimaryKey());
        return MysqlDatabase::query($this->stringify(), get_class($this->instance), true);
    }

    /**
     * Get request as string
     *
     * @return string
     * @throws Exception
     */
    public function stringify(): string
    {
        return $this->hasFields()
            . $this->hasFrom()
            . $this->hasJoin()
            . $this->hasConditions()
            . $this->hasGroup()
            . $this->hasOrder()
            . $this->hasLimit();
    }

    /**
     * Get the current model
     *
     * @return Model
     */
    public function getInstance(): Model
    {
        return $this->instance;
    }
}
