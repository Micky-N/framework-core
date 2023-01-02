<?php

namespace MkyCore;

class Session
{

    /**
     * Get all session data
     *
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Set session key value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get and remove session value
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    /**
     * Get session value
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Delete session value
     *
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check if session value exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Clear session
     *
     * @return bool
     */
    public function clear(): bool
    {
        return session_unset();
    }

    /**
     * Gets the session ID.
     *
     * @return string
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * Destroys the session.
     *
     * @return bool
     */
    public function destroy(): bool
    {
        return session_destroy();
    }

    /**
     * Checks if the session is started.
     *
     * @return bool
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}