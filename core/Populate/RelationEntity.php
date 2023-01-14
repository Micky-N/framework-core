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

class RelationEntity
{

    public function __construct(private readonly Populator $populator, private readonly ?Entity $entity = null)
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
        $populator->populate();
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
                $relation = $entity->$relation();
                $foreignKey = $relation->getForeignKey();
                $this->populator->merge(new LoopMerging([
                    $foreignKey => $lastEntity->{$lastEntity->getPrimaryKey()}()
                ]));
            }
        }
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

    /**
     * Insert a many-to-many database row in pivot
     *
     * @param Populator $populator
     * @param array $data
     * @param string|null $relation
     * @throws Exception
     */
    public function addOnPivot(Populator $populator, array $data = [], string $relation = null): void
    {
        if (!$this->entity) {
            return;
        }
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
                $populator->populate();
                $lastSaves = $populator->getLastSaves();
                $arrLast = array_slice($lastSaves, -$populator->getCount(), $populator->getCount());
                for ($i = 0; $i < count($arrLast); $i++) {
                    $ls = $arrLast[$i];
                    $testRes = $relation->attachOnPivot($ls, $data);
                    if ($testRes) {
                        Run::$count++;
                    }
                }
            }
        }
    }
}