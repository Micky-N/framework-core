<?php

namespace MkyCore\Console\Create;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Facades\DB;
use ReflectionException;

class Manager extends Create
{
    protected string $outputDirectory = 'Managers';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:manager']
    ];

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        $table = $this->moduleOptions['table'] ?? false;
        if(!$table){
            $getTables = array_map(function ($tables) {
                return $tables['Tables_in_'.DB::getDatabase()];
            }, DB::query('SHOW TABLES'));
            do {
                $confirm = true;
                $table = $this->sendQuestion('Enter the table name');
                if (!in_array($table, $getTables)) {
                    $this->sendError("Table not exists", $table);
                    $confirm = false;
                }
            } while (!$confirm);
        }

        $entity = $this->moduleOptions['entity'] ?? false;
        if(!$entity){
            do {
                $confirm = true;
                $entity = trim($this->sendQuestion('Enter the name of entity, or skip'));
                if ($entity) {
                    $confirm = $this->getModuleAndClass($entity, 'entities', '', $replaceParams['module'] ?? '');
                    if($confirm){
                        $entity = $confirm;
                    }
                }
            } while (!$confirm);
        }

        $replaceParams['table'] = $this->setTable($table, $entity);
        return $replaceParams;
    }

    protected function setTable(string $table, string $entity = ''): string
    {
        $res = "\n/**\n";
        $res .= " * @Table('$table')\n";
        if ($entity) {
            $res .= " * @Entity('$entity')\n";
        }
        $res .= " */";
        return $res;

    }
}