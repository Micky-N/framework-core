<?php

namespace MkyCore\Exceptions\Migration;

use Exception;

class MigrationException extends Exception
{

    public static function FILE_ALREADY_EXISTS(string $fileName): static
    {
        return new static("File $fileName already exists");
    }

    public static function DATABASE_MIGRATION_DIRECTORY_NOT_FOUND(): static
    {
        return new static("Database migration directory not found");
    }

    public static function MIGRATION_FILE_NOT_FOUND(string $fileName): static
    {
        return new static("File $fileName not found");
    }
}