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
        if(isset($_COOKIE[$id])){
            $cookie = $_COOKIE[$id];
            if(is_array($cookie)){
                foreach ($cookie as $key => $val){
                    $cookie[$key] = Crypt::decrypt($val);
                }
            }else{
                $cookie = Crypt::decrypt($cookie);
            }
            return $cookie;
        }
        return $default;
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
        return $this->set($id, '', '-1 hour');
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
                if (setcookie($id . "[$key]", Crypt::encrypt($val), $time, $path, $domain, $secure, $httpOnly)) {
                    $_COOKIE[$id] = array_replace_recursive(!empty($_COOKIE[$id]) && is_array($_COOKIE[$id]) ? $_COOKIE[$id] : [], [$key => Crypt::encrypt($val)]);
                } else {
                    return false;
                }
            }
        } else {
            if (setcookie($id, Crypt::encrypt($value), $time, $path, $domain, $secure, $httpOnly)) {
                $_COOKIE[$id] = Crypt::encrypt($value);
            } else {
                return false;
            }
        }
        return true;
    }
}