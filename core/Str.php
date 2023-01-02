<?php

namespace MkyCore;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

class Str
{
    public static function camelize(string $string): string
    {
        return self::inflection()->camelize($string);
    }

    private static function inflection(): Inflector
    {
        return InflectorFactory::create()->build();
    }

    public static function tableize(string $string): string
    {
        return self::inflection()->tableize($string);
    }

    public static function capitalize(string $string, string $delimiters = " \n\t\r\0\x0B-"): string
    {
        return self::inflection()->capitalize($string, $delimiters);
    }

    public static function classify(string $string): string
    {
        return self::inflection()->classify($string);
    }

    public static function pluralize(string $string): string
    {
        return self::inflection()->pluralize($string);
    }

    public static function singularize(string $string): string
    {
        return self::inflection()->singularize($string);
    }

    public static function slugify(string $string): string
    {
        return self::inflection()->urlize($string);
    }

    public static function unaccent(string $string): string
    {
        return self::inflection()->unaccent($string);
    }

    public static function toSnake(string $string): string
    {
        return preg_replace_callback('/[A-Z+]/', function ($exp) {
            if (isset($exp[0])) {
                return '_' . lcfirst($exp[0]);
            }
            return $exp[0];
        }, lcfirst($string));
    }

    public static function random(int $length = 16): string
    {
        try {
            return bin2hex(random_bytes($length));
        } catch (\Exception $e) {
            return '';
        }
    }

    public static function hashPassword(string $string, string $algo = PASSWORD_BCRYPT): string
    {
        return password_hash($string, $algo);
    }

    public static function passwordVerify(string $string, string $hash): bool
    {
        return password_verify($string, $hash);
    }
}