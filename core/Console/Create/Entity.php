<?php

namespace MkyCore\Console\Create;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Str;
use ReflectionException;

class Entity extends Create
{
    protected string $outputDirectory = 'Entities';

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        $manager = $this->moduleOptions['manager'] ?? false;
        $properties = [];
        if (!$manager) {
            do {
                $confirm = true;
                $manager = trim($this->sendQuestion('Enter the name of manager, or skip'));
                if ($manager) {
                    $confirm = $this->getModuleAndClass($manager, 'managers', 'manager', $replaceParams['module'] ?? '');
                    if ($confirm) {
                        $manager = $confirm;
                    }
                }
            } while (!$confirm);
        }
        do {
            $property = $this->sendQuestion('Set column', 'n/ to skip') ?: false;
            if ($property) {
                $properties[] = Str::camelize($property);
            }
        } while ($property);

        do {
            $confirm = true;
            $primaryKey = $this->sendQuestion('Set primary column', 'n/ to skip') ?: false;
            if ($primaryKey) {
                $primaryKey = Str::camelize($primaryKey);
                if(!in_array($primaryKey, $properties)){
                    $confirm = false;
                }
            }
        } while (!$confirm);

        $propertiesString = '';
        $gettersString = '';
        if ($properties) {
            $propertiesString = $this->setProperties($properties, $primaryKey ?: '');
            $gettersString = $this->setGetters($properties);
        }

        if ($manager) {
            $manager = $this->setManager($manager);
        }
        $replaceParams['manager'] = $manager;
        $replaceParams['properties'] = $propertiesString;
        $replaceParams['getters'] = $gettersString;
        return $replaceParams;
    }

    private function setProperties(array $properties, string $primaryKey = ''): string
    {
        $props = array_map(function ($prop) use ($primaryKey) {
            $propString = "private \$$prop;";
            if ($primaryKey && $primaryKey === $prop) {
                $propString = <<<PK
/**
    * @PrimaryKey
    */
    $propString
PK;
            }
            return $propString;
        }, $properties);

        return join("\n\t", $props);
    }

    private function setGetters(array $properties): string
    {
        $props = array_map(function ($prop) {
            $set = ucfirst($prop);
            $propString = <<<GETTER
public function $prop()
    {
        return \$this->$prop;
    }
    
    public function set$set(\$$prop)
    {
        \$this->$prop = \$$prop;
    }
GETTER;

            return $propString;
        }, $properties);
        return join("\n\n\t", $props);
    }

    private function setManager(string $manager): string
    {
        return <<<MANAGER

/**
 * @Manager('$manager')
 */
MANAGER;
    }
}