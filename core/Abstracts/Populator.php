<?php

namespace MkyCore\Abstracts;

use Closure;
use Faker\Factory;
use MkyCore\Console\Populator\Run;
use MkyCore\Populate\ArrayMerging;
use MkyCore\Populate\LoopMerging;
use MkyCore\Populate\RelationEntity;

abstract class Populator
{
    protected string $manager = '';
    protected \Faker\Generator $faker;
    protected int $count = 1;
    protected array $mergesData = [];
    protected array $addCallbacks = [];
    protected array $forCallbacks = [];
    protected array $addPivotCallbacks = [];
    protected array $lastSaves = [];
    protected array $order = [];
    protected int $index = 0;

    public function __construct()
    {
        $this->faker = \Faker\Factory::create(config('app.locale', Factory::DEFAULT_LOCALE));
        $this->faker->setDefaultTimezone(config('app.timezone', 'Europe/Paris'));
    }

    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function merge(LoopMerging $loopMerging): static
    {
        $loopMerging->setCount($this->count);
        for ($i = 0; $i < $this->count; $i++) {
            $oldMerge = $this->mergesData[$i] ?? [];
            $this->mergesData[$i] = array_merge($oldMerge, $loopMerging($i));
        }
        return $this;
    }

    public function adds(callable $addCallback): static
    {
        $this->addCallbacks[] = $addCallback;
        $this->order[] = 'operationAdd';
        return $this;
    }

    public function addsOnPivot(callable $addPivotCallback)
    {
        $this->addPivotCallbacks[] = $addPivotCallback;
        $this->order[] = 'operationPivot';
        return $this;
    }

    public function for(callable $forCallback)
    {
        $this->forCallbacks[] = $forCallback;
        return $this;
    }

    public function populate()
    {
        $this->operationFor();
        $this->operationPopulate();
        $this->useOperation();
    }

    /**
     * @return void
     */
    private function operationFor(): void
    {
        if ($this->forCallbacks) {
            $forCallbacks = $this->forCallbacks;
            for ($i = 0; $i < count($forCallbacks); $i++) {
                $this->handleForCallback($forCallbacks[$i]);
            }
        }
    }

    private function handleForCallback(Closure $forCallback)
    {
        $forCallback(new RelationEntity($this));
    }

    /**
     * @return void
     */
    private function operationPopulate(): void
    {
        for ($i = 0; $i < $this->count; $i++) {
            $merge = $this->mergesData[$i] ?? [];
            $merged = array_merge($this->definition(), $merge);
            $this->lastSaves[] = $this->getManager()->create($merged);
            Run::$count++;
        }
    }

    public function definition(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getManager(): Manager
    {
        return new $this->manager();
    }

    private function useOperation()
    {
        $operation = $this->getCurrentOperation();
        if ($operation) {
            $this->$operation();
            $this->useOperation();
        }
    }

    private function getCurrentOperation(): bool|string
    {
        $currentOperation = $this->order[$this->index] ?? false;
        if ($currentOperation) {
            $this->index ++;
            return $currentOperation;
        }
        $this->index = 0;
        return false;
    }

    /**
     * @return void
     */
    private function operationAdd(): void
    {
        $lastSaves = $this->lastSaves;
        for ($i = 0; $i < count($lastSaves); $i++) {
            $last = $lastSaves[$i];
            if ($this->addCallbacks) {
                $addCallbacks = $this->addCallbacks;
                for ($j = 0; $j < count($addCallbacks); $j++) {
                    $this->handleAddCallback($addCallbacks[$j], $last);
                }
            }
        }
    }

    private function handleAddCallback(Closure $addCallback, Entity $entity)
    {
        $addCallback(new RelationEntity($this, $entity));
    }

    /**
     * @return void
     */
    private function operationPivot(): void
    {
        $lastSaves = $this->lastSaves;
        for ($i = 0; $i < count($lastSaves); $i++) {
            $last = $lastSaves[$i];
            if ($this->addPivotCallbacks) {
                $addPivotCallbacks = $this->addPivotCallbacks;
                for ($k = 0; $k < count($addPivotCallbacks); $k++) {
                    $this->handleAddPivotCallback($addPivotCallbacks[$k], $last);
                }
            }
        }
    }

    private function handleAddPivotCallback(Closure $addCallback, Entity $entity)
    {
        $addCallback(new RelationEntity($this, $entity));
    }

    /**
     * @return Entity[]
     */
    public function getLastSaves(): array
    {
        return $this->lastSaves;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}