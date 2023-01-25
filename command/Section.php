<?php

namespace MkyCommand;

class Section
{

    /** @var string[] $texts */
    private array $texts;

    public function __construct(private int $maxLine = 0)
    {
    }

    public function text(string $text): static
    {
        if($this->maxLine){
            if($this->count() >= $this->maxLine){
                $this->clear(1);
            }
        }
        $this->texts[] = $text;
        return $this;
    }

    /**
     * @return array
     */
    public function getTexts(): array
    {
        return $this->texts;
    }

    /**
     * @return int
     */
    public function getMaxLine(): int
    {
        return $this->maxLine;
    }

    /**
     * @return void
     */
    public function read(): void
    {
        echo join("\n", $this->texts);
    }

    /**
     * @param int|null $numberLine
     * @return void
     */
    public function clear(?int $numberLine = null): void
    {
        if ($numberLine) {
            $this->texts = array_slice($this->texts, 0, -$numberLine);
        } else {
            $this->texts = [];
        }
    }

    /**
     * @param int|string $search
     * @param string $replace
     * @return $this
     */
    public function overwrite(int|string $search, string $replace): static
    {
        if(is_string($search)){
            $index = array_search($search, $this->texts);
            $this->texts[$index] = $replace;
        }else{
            $this->texts[$search] = $replace;
        }
        return $this;
    }

    /**
     * @param int $maxLine
     * @return Section
     */
    public function setMaxLine(int $maxLine): Section
    {
        $this->maxLine = $maxLine;
        return $this;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->texts);
    }
}