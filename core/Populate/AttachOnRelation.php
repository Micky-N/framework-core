<?php

namespace MkyCore\Populate;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Populator;
use MkyCore\RelationEntity\HasOne;
use ReflectionClass;
use ReflectionException;

class AttachOnRelation
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
            $word = strtolower(str_replace('Populator', '', $class));
            if (method_exists($entity, $word)) {
                $relationTest = $entity->$word();
                if ($relationTest instanceof HasOne) {
                    $relation = $word;
                }
            }
        }
        if ($relation) {
            if (method_exists($entity, $relation)) {
                $relation = $entity->$relation();
                if($relation instanceof HasOne){
                    $foreignKey = $relation->getForeignKey();
                    $this->populator->merge(new LoopMerging([
                        $foreignKey => $lastEntity->{$lastEntity->getPrimaryKey()}()
                    ]));
                }
            }
        }
    }
}