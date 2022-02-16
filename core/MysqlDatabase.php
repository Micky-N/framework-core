<?php

namespace MkyCore;

use DebugBar\DataCollector\PDO\TraceablePDO;
use Exception;
use PDO;

class MysqlDatabase
{
    private static $connection;

    /**
     * Create and get PDO connection
     *
     * @return TraceablePDO|PDO
     * @throws Exception
     */
    public static function getConnection()
    {
        if(is_null(self::$connection) || !method_exists(self::$connection, 'getAttribute') || self::$connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql'){
            $config = config('connections.mysql', 'database');
            $dsn = 'mysql:dbname=' . $config['name'] . ';host=' . $config['host'];
            $pdo = new PDO($dsn, $config['user'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query("SET NAMES utf8");
            self::$connection = $pdo;
            if(config('env') == 'local'){
                self::$connection = new TraceablePDO(self::$connection);
            }
        }
        return self::$connection;
    }

    /**
     * Run query request
     *
     * @param $statement
     * @param null $class_name
     * @param bool $one
     * @return array|mixed
     * @throws Exception
     */
    public static function query($statement, $class_name = null, $one = false)
    {
        $req = self::getConnection()->query($statement);
        $class_name === null ? $req->setFetchMode(PDO::FETCH_ASSOC) : $req->setFetchMode(PDO::FETCH_CLASS, $class_name);
        return $one ? $req->fetch() : $req->fetchAll();
    }

    /**
     * Run prepare request
     *
     * @param $statement
     * @param $attribute
     * @param null $class_name
     * @param bool $one
     * @return array|bool|mixed
     * @throws Exception
     */
    public static function prepare($statement, $attribute, $class_name = null, $one = false)
    {
        $req = self::getConnection()->prepare($statement);
        $res = $req->execute($attribute);
        if(
            strpos($statement, 'UPDATE') === 0 ||
            strpos($statement, 'INSERT') === 0 ||
            strpos($statement, 'DELETE') === 0
        ){
            return $res;
        }
        $class_name === null ? $req->setFetchMode(PDO::FETCH_OBJ) : $req->setFetchMode(PDO::FETCH_CLASS, $class_name);
        return $one ? $req->fetch() : $req->fetchAll();
    }
}