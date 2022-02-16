<?php

namespace MkyCore\Traits;

use MkyCore\QueryBuilderMysql;


/**
 * Trait and facade for \MkyCore\QueryBuilderMysql
 *
 * @method static \MkyCore\QueryBuilderMysql where()
 * @method static \MkyCore\QueryBuilderMysql select()
 * @method static \MkyCore\QueryBuilderMysql from(string $table, $alias = null)
 * @method static \MkyCore\QueryBuilderMysql join(string $join_table, string $on, string $operation, string $to, string $aliasFirstTable = '')
 * @method static \MkyCore\QueryBuilderMysql first()
 * @method static \MkyCore\QueryBuilderMysql query(string $statement)
 * @method static \MkyCore\QueryBuilderMysql prepare(string $statement, array $attribute)
 * @method static \MkyCore\QueryBuilderMysql orderBy()
 * @method static \MkyCore\QueryBuilderMysql limit()
 * @method static \MkyCore\QueryBuilderMysql groupBy()
 * @method static array map(string $key, $value = null)
 * @method static array get()
 * @method static array toArray()
 * @method static \MkyCore\Model|bool last()
 * @method static string stringify()
 *
 * @see \MkyCore\QueryBuilderMysql
 */
trait QueryMysql
{

    /**
     * @var QueryBuilderMysql|null
     */
    public static ?QueryBuilderMysql $query = null;

    public static function __callStatic($method, $arguments)
    {
        if (is_null(self::$query) || self::$query->getInstance() != get_called_class()) {
            self::$query = new QueryBuilderMysql(get_called_class());
        }
        return call_user_func_array([self::$query, $method], $arguments);
    }
}
