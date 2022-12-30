<?php

namespace MkyCore;

use DateTime;

class Cookie
{

    /**
     * Get cookie value and delete it
     *
     * @param string $id
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $id, mixed $default = null): mixed
    {
        $value = $this->get($id, $default);
        $this->remove($id);
        return $value;
    }

    /**
     * Get cookie value
     *
     * @param string $id
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $id, mixed $default = null): mixed
    {
        return $_COOKIE[$id] ?? $default;
    }

    /**
     * Delete cookie value
     *
     * @param string $id
     * @return bool
     */
    public function remove(string $id): bool
    {
        unset($_COOKIE[$id]);
        return $this->set($id, '', time() - 3600);
    }

    /**
     * Set value in cookie
     *
     * @param string $id
     * @param string|array $value
     * @param int|DateTime|string $time
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return bool
     */
    public function set(string $id, string|array $value, int|DateTime|string $time = 1, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = true): bool
    {
        if ($time instanceof DateTime) {
            $time = $time->getTimestamp();
        } elseif (is_string($time)) {
            $date = new DateTime();
            $date->modify($time);
            $time = $date->getTimestamp();
        } elseif (is_integer($time)) {
            $time = time() + 60 * 60 * 24 * $time;
        }
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                if (setcookie($id . "[$key]", $val, $time, $path, $domain, $secure, $httpOnly)) {
                    $_COOKIE[$id] = array_replace_recursive($_COOKIE[$id] ?? [], [$key => $val]);
                } else {
                    return false;
                }
            }
        } else {
            if (setcookie($id, $value, $time, $path, $domain, $secure, $httpOnly)) {
                $_COOKIE[$id] = $value;
            } else {
                return false;
            }
        }
        return true;
    }
}