<?php

namespace MkyCore;

use Exception;
use PDO;
use PDOException;

class Database
{

    private static ?PDO $connection = null;
    private static array $config = [];

    /**
     * Create and get PDO connection
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO
    {
        if (is_null(self::$connection) || !method_exists(self::$connection, 'getAttribute') || self::$connection->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            $default = config('database.default', 'mysql');
            $config = config('database.connections.'.$default);
            self::$config = $config;
            $pdo = match ($default){
                'sqlite' => self::setSqlitePDO($config),
                default => self::setMysqlPDO($config)
            };
            $pdo->query("SET NAMES utf8");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->query("SET NAMES utf8");
            self::$connection = $pdo;
        }
        return self::$connection;
    }

    private static function setMysqlPDO(array $config): PDO
    {
        return new PDO("mysql:host={$config['host']};dbname={$config['name']}", $config['user'], $config['password']);
    }

    private static function setSqlitePDO(array $config): PDO
    {
        return new PDO('sqlite:'.$config['file']);
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
    public static function query($statement, $class_name = null, bool $one = false): mixed
    {
        $req = self::getConnection()->query($statement);
        $req->setFetchMode(PDO::FETCH_ASSOC);
        if ($class_name) {
            return $one ? new $class_name($req->fetch()) : array_map(fn($fetch) => new $class_name($fetch), $req->fetchAll());
        }
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
    public static function prepare($statement, $attribute, $class_name = null, bool $one = false): mixed
    {
        $getPdoParams = self::getPdoParams($attribute);
        $req = self::getConnection()->prepare($statement);
        foreach ($attribute as $key => $value) {
            $req->bindValue(":$key", $value, $getPdoParams[$key]);
        }
        $res = $req->execute();
        if (
            str_starts_with($statement, 'UPDATE') ||
            str_starts_with($statement, 'INSERT') ||
            str_starts_with($statement, 'DELETE')
        ) {
            return $res;
        }
        $req->setFetchMode(PDO::FETCH_ASSOC);
        if ($class_name) {
            return $one ? new $class_name($req->fetch()) : array_map(fn($fetch) => new $class_name($fetch), $req->fetchAll());
        }
        return $one ? $req->fetch() : $req->fetchAll();
    }

    private static function getPdoParams(array $arrayEntity): array
    {
        $res = [];
        foreach ($arrayEntity as $key => $value) {
            if (is_numeric($value)) {
                $res[$key] = PDO::PARAM_INT;
            } else if (is_bool($value)) {
                $res[$key] = PDO::PARAM_BOOL;
            } else {
                $res[$key] = PDO::PARAM_STR;
            }
        }
        return $res;
    }

    /**
     * @param mixed $config
     * @param string $connection
     * @return PDO
     * @throws PDOException
     */
    private static function getPdo(mixed $config, string $connection): PDO
    {
        $startDsn = "$connection:";
        switch ($connection) {
            case 'mysql':
                $dsn = $startDsn . 'dbname=' . $config['name'] . ';host=' . $config['host'];
                $pdo = new PDO($dsn, $config['user'], $config['password']);
                break;
            case 'sqlite':
                $dsn = $startDsn . $config['filename'];
                $pdo = new PDO($dsn, $config['user'], $config['password']);
                break;
            default:
                throw new PDOException('Unknown connection server');
        }
        return $pdo;
    }

    public static function getDatabase(): string
    {
        return static::$config['name'];
    }
}