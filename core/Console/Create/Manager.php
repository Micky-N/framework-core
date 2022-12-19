<?php

namespace MkyCore\Console\Create;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
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
        if (!$table) {
            $table = $this->sendQuestion('Enter the table name', 'n/ to skip');
        }

        $entity = $this->moduleOptions['entity'] ?? false;
        if (!$entity) {
            do {
                $confirm = true;
                $name = lcfirst(str_replace('Manager', '', $replaceParams['name']));
                $entity = trim($this->sendQuestion('Enter the name of entity', 'n/ to skip'));
                if ($entity) {
                    $confirm = $this->getModuleAndClass($entity, 'entities', '', $replaceParams['module'] ?? '');
                    if ($confirm) {
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