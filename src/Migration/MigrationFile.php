<?php

namespace MkyCore\Migration;

use MkyCore\Abstracts\Migration;
use MkyCore\Application;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\Migration\MigrationException;
use MkyCore\File;
use ReflectionException;

class MigrationFile
{

    const TYPES = ['creation', 'alteration'];
    private string $databaseDir;

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function __construct(private readonly Application $app)
    {
        $this->databaseDir = File::makePath([$this->app->get('path:base'), 'database', 'migrations']);
    }

    /**
     * @param string $direction
     * @param string|null $file
     * @throws FailedToResolveContainerException
     * @throws MigrationException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    public function actionMigration(string $direction = 'up', ?string $file = null): void
    {
        if (!file_exists($this->databaseDir)) {
            throw MigrationException::DATABASE_MIGRATION_DIRECTORY_NOT_FOUND();
        }
        $this->browser_dir($direction, $file);
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws MigrationException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function browser_dir(string $direction = 'up', ?string $file = null): void
    {
        if ($file) {
            $migrationFile = File::makePath([$this->app->get('path:database'), 'migrations', "{$file}.php"], true);
            if (!$migrationFile) {
                throw MigrationException::MIGRATION_FILE_NOT_FOUND($migrationFile);
            }
            $this->instantiateMigration($direction, require $migrationFile);
        } else {
            $dir = glob(File::makePath([$this->databaseDir, "*"]));
            $dir = $this->sortMigrationFile($dir);
            dd($dir);
            $migrationFiles = [];
            for ($i = 0; $i < count($dir); $i++) {
                $migrationFile = $dir[$i];
                $type = str_contains($migrationFile, 'alter_') ? 'alteration' : 'creation';
                require $migrationFile;
                $class = $this->getClassFromFile($migrationFile);
                $instantiateMigration = new $class();
                $instantiateMigration->{$direction}();
            }
        }
    }

    /**
     * @param string $direction
     * @param Migration $migration
     * @return void
     */
    private function instantiateMigration(string $direction, Migration $migration)
    {
        if (in_array($direction, ['up', 'down'])) {
            if (method_exists($migration, $direction)) {
                $migration->{$direction}();
            }
        }
    }

    private function sortMigrationFile(array $dir): array
    {
        usort($dir, function ($file1, $file2) {
            return $this->compareFileTime($file1, $file2) ? -1 : 1;
        });
        return $dir;
    }

    private function compareFileTime(string $file1, string $file2): bool
    {
        $file1 = str_replace([$this->app->get('path:database') . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR, '.php'], '', $file1);
        $file2 = str_replace([$this->app->get('path:database') . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR, '.php'], '', $file2);
        $file1 = preg_replace('/[_a-z+]/', '', $file1);
        $file2 = preg_replace('/[_a-z+]/', '', $file2);
        return (int) $file2 > (int) $file1;
    }

    private function getClassFromFile(string $file): string
    {
        $text = preg_replace('/[0-9+]/', '', $file);
        $text = str_replace([$this->app->get('path:database') . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR, '.php'], '', $text);
        return $this->toPascal($text);
    }

    private function toPascal(string $text): string
    {
        return preg_replace_callback('/_[a-z]/', function ($exp) {
            if (isset($exp[0])) {
                return str_replace('_', '', strtoupper($exp[0]));
            }
        }, $text);
    }
}
