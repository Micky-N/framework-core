<?php

namespace MkyCore\Console\Migration;

use Exception;
use MkyCore\Config;
use MkyCore\Console\Create\Create as AbstractCreate;
use MkyCore\Migration\MigrationFile;
use MkyCore\Migration\Schema;

abstract class Migration extends AbstractCreate
{
    public static bool $query = false;
    protected string $direction = '';

    public function process(): bool|string
    {
        $config = $this->getConfigDatabase();
        $migrationRunner = new MigrationFile($this->app, $config);
        self::$query = in_array('--query', $this->params);
        $params = $this->params;
        $file = isset($params['arg0']) && !str_starts_with($params['arg0'], '--') ? $params['arg0'] : null;
        try {
            $migrationRunner->actionMigration($this->direction, $file);
            if($success = Schema::$SUCCESS){
                for ($i = 0; $i < count($success); $i++){
                    $response = $success[$i];
                    echo $this->getColoredString($response[0], 'green', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
                }
            }
            if($errors = Schema::$ERRORS){
                for ($i = 0; $i < count($errors); $i++){
                    $response = $errors[$i];
                    echo $this->getColoredString($response[0], 'red', 'bold') . (isset($response[1]) ? ": $response[1]" : '') . "\n";
                }
            }
            return true;
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

    /**
     * @return mixed
     * @throws \MkyCore\Exceptions\Container\FailedToResolveContainerException
     * @throws \MkyCore\Exceptions\Container\NotInstantiableContainerException
     * @throws \ReflectionException
     */
    private function getConfigDatabase(): array
    {
        $configDatabase = $this->app->get(Config::class)->get('database');
        $default = $configDatabase['default'];
        return $configDatabase['connections'][$default];
    }
}