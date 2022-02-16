<?php


namespace MkyCore;


class Session
{

    const FLASH = 'FLASH_MESSAGES';
    const FLASH_ERROR = 'error';
    const FLASH_SUCCESS = 'success';
    const FLASH_MESSAGE = 'flashMessage';

    /**
     * Run new session
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE && empty(session_id())) {
            session_start();
        }
    }

    /**
     * Get session value from key
     * if not get default value
     *
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
    }

    /**
     * Set key value to session
     *
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Delete session key
     *
     * @param string $key
     */
    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Set message on flash session type
     *
     * @param string $type
     * @param string $name
     * @param $message
     */
    public function setFlashMessageOnType(string $type, string $name, $message): void
    {
        if (isset($_SESSION[self::FLASH][$type][$name])) {
            unset($_SESSION[self::FLASH][$type][$name]);
        }
        $_SESSION[self::FLASH][$type][$name] = $message;
    }

    /**
     * Get flash type message
     *
     * @param string $type
     * @return array|void
     */
    public function getFlashMessagesByType(string $type)
    {
        if (!isset($_SESSION[self::FLASH][$type])) {
            return;
        }
        $flashType = $_SESSION[self::FLASH][$type];
        unset($_SESSION[self::FLASH][$type]);
        return $flashType;
    }

    /**
     * Get all session value
     *
     * @return array
     */
    public function getAll()
    {
        return $_SESSION;
    }

    /**
     * Get constant variable value
     *
     * @param $const
     * @return mixed
     */
    public function getConstant($const)
    {
        if(defined("self::$const")){
            return constant("self::$const");
        }
        return null;
    }
}
