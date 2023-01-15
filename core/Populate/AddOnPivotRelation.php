<?php

namespace MkyCore\Populate;

use Exception;
use MkyCore\Abstracts\Entity;
use MkyCore\Abstracts\Populator;
use MkyCore\Console\Populator\Run;
use MkyCore\RelationEntity\HasMany;
use MkyCore\RelationEntity\HasOne;
use MkyCore\RelationEntity\ManyToMany;
use MkyCore\Str;
use ReflectionClass;

class AddOnPivotRelation
{

    public function __construct(private readonly ?Entity $entity = null)
    {
    }

    /**
     * Insert a many-to-many database row in pivot
     *
     * @param Populator $populator
     * @param array $data
     * @param string|null $relation
     * @throws Exception
     */
    public function add(Populator $populator, array $data = [], string $relation = null): void
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
                    if ($relationTest instanceof ManyToMany) {
                        $relation = $word;
                        break;
                    }
                }
            }
        }
        if ($relation) {
            if (method_exists($this->entity, $relation)) {
                $relation = $this->entity->$relation();
                if ($relation instanceof ManyToMany) {
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
}