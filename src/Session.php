<?php

namespace MkyCore;

class Session
{

    public function __construct(array $option = [])
    {
        if(empty($_SESSION) && session_status() === PHP_SESSION_NONE && empty(session_id())){
            session_start($option);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function all(): array
    {
        return $_SESSION;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->remove($key);
        return $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): bool
    {
        return session_unset();
    }

    /**
     * Gets the session ID.
     */
    public function id(): string
    {
        return session_id();
    }

    /**
     * Destroys the session.
     *
     */
    public function destroy(): bool
    {
        return session_destroy();
    }

    /**
     * Checks if the session is started.
     */
    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}