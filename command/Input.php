<?php

namespace MkyCommand;

use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;

class Input
{
    private readonly string $file;
    private readonly string $signature;
    private array $options;
    private array $arguments;

    public function __construct(array $inputs)
    {
        $this->file = array_shift($inputs);
        $this->signature = array_shift($inputs);
        $this->options = $this->parseInputsToOptions($inputs);
        $this->arguments = $this->parseInputsToArguments($inputs);
    }

    private function parseInputsToOptions(array &$inputs): array
    {
        if($inputs){
            $options = [];
            for($i = 0; $i < count($inputs); $i++){
                $resOption = null;
                if(!empty($inputs[$i])){
                    $input = $inputs[$i];
                    $key = $i;
                    if(str_starts_with($input, '--') && $input[2] !== '-'){
                        $explodeInputs = explode('=', $input);
                        $key = str_replace('--', '', trim($explodeInputs[0]));
                        $resOption = !empty($explodeInputs[1]) ? trim($explodeInputs[1]) : false;
                    }elseif(str_starts_with($input, '-') && $input[1] !== '-' && strlen($input) == 2){
                        $key = $input[1];
                        if(isset($inputs[$i + 1]) && !str_starts_with($inputs[$i + 1], '-') && !str_contains($inputs[$i + 1], '=')){
                            $resOption = trim($inputs[$i + 1]);
                            array_splice($inputs, $i + 1, 1);
                        }else{
                            $resOption = false;
                        }
                    }elseif(str_starts_with($input, '-') && $input[1] !== '-' && strlen($input) > 2){
                        $key = $input[1];
                        $value = str_replace("-$key", '', $input);
                        $resOption = $value;
                    }
                    if(!isset($options[$key])){
                        $options[$key] = $resOption;
                    }else if(isset($options[$key])){
                        $options[$key] = is_array($options[$key]) ? array_push($options[$key], $resOption) : [$options[$key], $resOption];
                    }
                }
            }
            return $options;
        }
        return [];
    }

    private function parseInputsToArguments(array &$inputs): array
    {
        if($inputs){
            $options = [];
            for($i = 0; $i < count($inputs); $i++){
                if(!empty($inputs[$i])){
                    $input = $inputs[$i];
                    if(!str_contains($input, '-')){
                        if(str_contains($input, '=')){
                            $explodeInputs = explode('=', $input);
                            $options[trim($explodeInputs[0])] = trim($explodeInputs[1]);
                        }else{
                            $options[$i] = $input;
                        }
                    }
                }
            }
            return $options;
        }
        return [];
    }

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param string $key
     * @param array $option
     * @return Input
     */
    public function addOption(string $key, mixed $option): static
    {
        $this->options[$key] = $option;
        return $this;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $key
     * @param mixed $argument
     * @return Input
     */
    public function addArgument(string $key, mixed $argument): static
    {
        $this->arguments[$key] = $argument;
        return $this;
    }

    public function deleteArgument(string $name): void
    {
        unset($this->arguments[$name]);
    }

    public function deleteOption(string $name): void
    {
        unset($this->options[$name]);
    }

    /**
     * @throws CommandException
     */
    public function getArgument(string $name)
    {
        if(!$this->hasArgument($name)){
            throw CommandException::ArgumentNotFound($name);
        }
        $argument = $this->arguments[$name];
        if(is_null($argument)){
            return null;
        }
        if($argument instanceof InputArgument){
            return $argument->getValue();
        }
        return $argument;
    }

    /**
     * @throws CommandException
     */
    public function getOption(string $name)
    {
        if(!$this->hasOption($name)){
            throw CommandException::OptionNotFound($name);
        }
        $options = $this->options[$name];
        if(is_null($options)){
            return null;
        }
        if($options instanceof InputOption){
            return $options->getValue();
        }
        return $options;
    }
}