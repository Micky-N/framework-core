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

    public function __invoke(int $index): array
    {
        $this->index = $index;
        return $this->handleMode();
    }

    /**
     * @param int $count
     * @return LoopMerging
     */
    public function setCount(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function getByModulo(): array
    {
        $modulo = $this->index % count($this->data);
        return $this->data[$modulo] ?? [];
    }

    /**
     * @return int
     */
    public function index(): int
    {
        return $this->index;
    }
    
    private function handleMode()
    {
        if(!$this->modeCallable){
            return $this->getByModulo();
        }
        return $this->resolve();
    }
    
    public function resolve()
    {
        $resolve = $this->callback;
        return $resolve($this);
    }


}