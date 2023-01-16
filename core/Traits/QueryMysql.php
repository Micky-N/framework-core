<?php

namespace MkyCore\Traits;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\QueryBuilderMysql;


/**
 * Trait and facade for \MkyCore\QueryBuilderMysql
 *
 * @method QueryBuilderMysql where(string $condition, ...$args)
 * @method QueryBuilderMysql select(...$args)
 * @method QueryBuilderMysql from(string $table, $alias = null)
 * @method QueryBuilderMysql join(string $join_table, string $on, string $operation, string $to, string $aliasFirstTable = '', string $type = 'left')
 * @method QueryBuilderMysql first()
 * @method QueryBuilderMysql query(string $statement)
 * @method QueryBuilderMysql prepare(string $statement, array $attribute)
 * @method QueryBuilderMysql orderBy(...$args)
 * @method QueryBuilderMysql limit(...$args)
 * @method QueryBuilderMysql groupBy(...$args)
 * @method array map(string $key, $value = null)
 * @method array get()
 * @method array toArray()
 * @method Entity|bool last()
 * @method string stringify()
 *
 * @see \MkyCore\QueryBuilderMysql
 */
trait QueryMysql
{

    /**
     * @throws Exception
     */
    public function __call(string $method, $arguments)
    {
        $queryBuilder = new QueryBuilderMysql($this->db, $this);
        return call_user_func_array([$queryBuilder, $method], $arguments);
    }
}
