<?php

namespace MkyCore\Populate;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Populator;
use MkyCore\Console\Populator\Run;
use MkyCore\RelationEntity\HasMany;
use MkyCore\RelationEntity\HasOne;
use MkyCore\Str;
use ReflectionClass;
use ReflectionException;

class AttachRelation
{

    public function __construct(private readonly Populator $populator)
    {
    }

    /**
     * Insert a many-to-one database row
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function attach(Populator $populator, string $relation = null): void
    {
        $populator->count(1)->populate();
        $last = $populator->getLastSaves();
        $lastEntity = $last[0];
        $entity = $this->populator->getManager()->getEntity();
        /** @var Entity $entity */
        $entity = new $entity([]);
        if (!$relation) {
            $class = new ReflectionClass($populator);
            $class = $class->getShortName();
            $class = strtolower(str_replace('Populator', '', $class));
            $words = [Str::pluralize($class), $class];
            for ($i = 0; $i < count($words); $i++) {
                $word = $words[$i];
                if (method_exists($entity, $word)) {
                    $relationTest = $entity->$word();
                    if ($relationTest instanceof HasMany || $relationTest instanceof HasOne) {
                        $relation = $word;
                        break;
                    }
                }
            }
        }
        if ($relation) {
            if (method_exists($entity, $relation)) {
                /** @var HasOne|HasMany $relation */
                $relation = $entity->$relation();
                $foreignKey = $relation->getForeignKey();
                $this->populator->merge(new LoopMerging([
                    $foreignKey => $lastEntity->{$lastEntity->getPrimaryKey()}()
                ]));
            }
        }
    }
}