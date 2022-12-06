<?php

namespace Console\Create;

use MkyCore\Facades\DB;

class Manager extends Create
{
    protected string $outputDirectory = 'Managers';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:manager']
    ];

    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        if(!($table = $this->moduleOptions['table'])){
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

        if(!($entity = $this->moduleOptions['entity'])){
            do {
                $confirm = true;
                $entity = trim($this->sendQuestion('Enter the name of entity, or skip'));
                if ($entity) {
                    $confirm = $this->getModuleAndClass($entity, 'entities');
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