<?php

namespace MkyCore\Traits;

use MkyCore\QueryBuilderMysql;


/**
 * Trait and facade for \MkyCore\QueryBuilderMysql
 *
 * @method \MkyCore\QueryBuilderMysql where(string $condition, ...$args)
 * @method \MkyCore\QueryBuilderMysql select(...$args)
 * @method \MkyCore\QueryBuilderMysql from(string $table, $alias = null)
 * @method \MkyCore\QueryBuilderMysql join(string $join_table, string $on, string $operation, string $to, string $aliasFirstTable = '')
 * @method \MkyCore\QueryBuilderMysql first()
 * @method \MkyCore\QueryBuilderMysql query(string $statement)
 * @method \MkyCore\QueryBuilderMysql prepare(string $statement, array $attribute)
 * @method \MkyCore\QueryBuilderMysql orderBy(...$args)
 * @method \MkyCore\QueryBuilderMysql limit(...$args)
 * @method \MkyCore\QueryBuilderMysql groupBy(...$args)
 * @method array map(string $key, $value = null)
 * @method array get()
 * @method array toArray()
 * @method \MkyCore\Abstracts\Entity|bool last()
 * @method string stringify()
 *
 * @see \MkyCore\QueryBuilderMysql
 */
trait QueryMysql
{

    /**
     * @throws \Exception
     */
    public function __call(string $method, $arguments)
    {
        $queryBuilder = new QueryBuilderMysql($this);
        return call_user_func_array([$queryBuilder, $method], $arguments);
    }
}
