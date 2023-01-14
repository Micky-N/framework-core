<?php

namespace MkyCore\Populate;

use Closure;

class LoopMerging
{

    private int $count = 1;
    private int $index = 0;
    private array $data = [];
    private ?Closure $callback = null;
    private bool $modeCallable = true;

    public function __construct(...$data)
    {
        if(is_array($data[0])){
            $this->modeCallable = false;
            $this->data = $data;
        }elseif(is_callable($data[0])){
            $this->callback = array_shift($data);
        }
    }

    /**
     * Set the current loop index
     * and run the handler method
     *
     * @param int $index
     * @return array
     */
    public function __invoke(int $index): array
    {
        $this->index = $index;
        return $this->handleMode();
    }

    /**
     * Set count
     *
     * @param int $count
     * @return LoopMerging
     */
    public function setCount(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Get data by modulo calcul
     *
     * @return array
     */
    private function getDataByModulo(): array
    {
        $modulo = $this->index % count($this->data);
        return $this->data[$modulo] ?? [];
    }

    /**
     * Get index
     *
     * @return int
     */
    public function index(): int
    {
        return $this->index;
    }

    /**
     * Get data if merge with array
     * or resolve callback if merge with callback
     *
     * @return mixed
     */
    private function handleMode(): mixed
    {
        if(!$this->modeCallable){
            return $this->getDataByModulo();
        }
        return $this->resolve();
    }

    /**
     * Resolve callback
     *
     * @return mixed
     */
    private function resolve(): mixed
    {
        $resolve = $this->callback;
        return $resolve($this);
    }

    /**
     * Get count
     *
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }


}