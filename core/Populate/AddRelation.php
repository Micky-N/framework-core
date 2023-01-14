<?php

namespace MkyCore\Populate;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Populator;
use MkyCore\RelationEntity\HasMany;
use MkyCore\RelationEntity\HasOne;
use MkyCore\Str;
use ReflectionClass;
use ReflectionException;

class AddRelation
{

    public function __construct(private readonly ?Entity $entity = null)
    {
    }

    /**
     * Insert a one-to-many database row
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function add(Populator $populator, string $relation = null): void
    {
        if (!$relation) {
            $class = new ReflectionClass($populator);
            $class = $class->getShortName();
            $class = strtolower(str_replace('Populator', '', $class));
            $words = [Str::pluralize($class), $class];
            for ($i = 0; $i < count($words); $i++) {
                $word = $words[$i];
                if (method_exists($this->entity, $word)) {
                    $relationTest = $this->entity->$word();
                    if ($relationTest instanceof HasMany || $relationTest instanceof HasOne) {
                        $relation = $word;
                        break;
                    }
                }
            }
        }
        if ($relation) {
            if (method_exists($this->entity, $relation)) {
                $relation = $this->entity->$relation();
                if ($relation instanceof HasMany || $relation instanceof HasOne) {
                    $foreignKey = $relation->getForeignKey();
                    $populator->merge(new LoopMerging([
                        $foreignKey => $this->entity->{$this->entity->getPrimaryKey()}()
                    ]))->populate();
                }
            }
        }
    }
}