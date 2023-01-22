<?php

namespace MkyCommand;

use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;

abstract class AbstractCommand
{
    protected string $signature = '';

    /**
     * @var InputArgument[]
     */
    protected array $arguments = [];

    /**
     * @var InputOption[]
     */
    protected array $options = [];

    public function __construct()
    {

    }

    abstract public function execute(Input $input): mixed;

    /**
     * @return string
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * @param string $signature
     * @return $this
     */
    public function setSignature(string $signature): static
    {
        $this->signature = $signature;
        return $this;
    }

    /**
     * @throws CommandException
     */
    public function setRealInput(Input $input): void
    {
        $this->setRealArguments($input);
        $this->setRealOptions($input);
    }

    /**
     * @throws CommandException
     */
    private function setRealArguments(Input &$input): void
    {
        $commandArguments = $this->getArguments();
        $commandArgumentsKeys = array_keys($commandArguments);
        $inputArguments = $input->getArguments();
        foreach ($commandArguments as $name => $commandArgument) {
            $index = array_search($name, $commandArgumentsKeys);
            if (empty($inputArguments[$index])) {
                if (in_array($commandArgument->getType(), [InputArgument::REQUIRED, InputArgument::IS_ARRAY | InputArgument::REQUIRED])) {
                    throw CommandException::ArgumentNotFound($name);
                }
                $inputArguments[$index] = false;
            } else {
                if (in_array($commandArgument->getType(), [InputArgument::IS_ARRAY, InputArgument::IS_ARRAY | InputArgument::REQUIRED, InputArgument::IS_ARRAY | InputArgument::OPTIONAL])) {
                    $inputArguments[$index] = $inputArguments;
                }
            }
            $commandArgument->setValue($inputArguments[$index]);
        }
        $input->setArguments($commandArguments);
    }

    /**
     * @return InputArgument[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @throws CommandException
     */
    private function setRealOptions(Input &$input): void
    {
        $commandOptions = $this->getOptions();
        $inputOptions = $input->getOptions();
        foreach ($commandOptions as $name => $commandOption) {
            $inputOptions = $this->getLongInputOptionCommand($inputOptions, $name, $commandOption);
            $inputOptions = $this->getShortInputOptionCommand($inputOptions, $name, $commandOption);
            $commandOption->setValue($inputOptions[$name]);
        }
        $input->setOptions($commandOptions);
    }

    /**
     * @return InputOption[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $inputOptions
     * @param int|string $name
     * @param InputOption $commandOption
     * @return array
     * @throws CommandException
     */
    private function getLongInputOptionCommand(array $inputOptions, int|string $name, InputOption $commandOption): array
    {
        if (isset($inputOptions[$commandOption->getShortname()])) {
            return $inputOptions;
        }
        if (!isset($inputOptions[$name])) {
            if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::IS_ARRAY | InputOption::OPTIONAL, InputOption::NEGATIVE])) {
                if ($commandOption->getType() === InputOption::NEGATIVE && array_key_exists("no-$name", $inputOptions)) {
                    $inputOptions = $this->replaceKey($inputOptions, "no-$name", $name);
                } else {
                    $inputOptions[$name] = null;
                }
            } else if ($commandOption->getType() === InputOption::NONE) {
                $inputOptions[$name] = false;
            } else if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else if (in_array($commandOption->getType(), [InputOption::REQUIRED, InputOption::IS_ARRAY | InputOption::REQUIRED])) {
                throw CommandException::OptionNotFound($name);
            }
        } else if (!$inputOptions[$name]) {
            if (in_array($commandOption->getType(), [InputOption::NONE, InputOption::NONE | InputOption::REQUIRED, InputOption::NONE | InputOption::OPTIONAL, InputOption::NEGATIVE])) {
                $inputOptions[$name] = true;
            } else if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::IS_ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = null;
            } else if ($commandOption->getType() === InputOption::NONE) {
                $inputOptions[$name] = true;
            }
        } else {
            if (in_array($commandOption->getType(), [InputOption::IS_ARRAY, InputOption::IS_ARRAY | InputOption::REQUIRED, InputOption::IS_ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = (array)$inputOptions[$name];
            }
        }
        return $inputOptions;
    }

    /**
     * @param array $inputOptions
     * @param int|string $name
     * @param InputOption $commandOption
     * @return array
     * @throws CommandException
     */
    private function getShortInputOptionCommand(array $inputOptions, int|string $name, InputOption $commandOption): array
    {
        if (isset($inputOptions[$name])) {
            return $inputOptions;
        }
        $shortOption = $commandOption->getShortname();
        if (!isset($inputOptions[$shortOption])) {
            if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::IS_ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = null;
            } else if ($commandOption->getType() == InputOption::NONE) {
                $inputOptions[$name] = false;
            } else if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else if (in_array($commandOption->getType(), [InputOption::REQUIRED, InputOption::IS_ARRAY | InputOption::REQUIRED])) {
                throw CommandException::OptionNotFound($name);
            }
        } else if (!$inputOptions[$shortOption]) {
            if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::IS_ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = null;
            } else if ($commandOption->getType() == InputOption::NONE) {
                $inputOptions[$name] = true;
            }
        } else {
            $inputOptions[$name] = $inputOptions[$shortOption];
            unset($inputOptions[$shortOption]);
            if (in_array($commandOption->getType(), [InputOption::IS_ARRAY, InputOption::IS_ARRAY | InputOption::REQUIRED, InputArgument::IS_ARRAY | InputArgument::OPTIONAL])) {

            }
        }
        return $inputOptions;
    }

    protected function addArgument(string $name, string $type, string $description): static
    {
        $this->arguments[$name] = new InputArgument($name, $type, $description);
        return $this;
    }

    protected function addOption(string $name, ?string $shortName, string $type, string $description, string|array|bool $default = null): static
    {
        $this->options[$name] = new InputOption($name, $shortName, $type, $description, $default);
        return $this;
    }

    private function replaceKey(array $arr, string $oldKey, string $newKey): array
    {
        if (array_key_exists($oldKey, $arr)) {
            $keys = array_keys($arr);
            $keys[array_search($oldKey, $keys)] = $newKey;
            return array_combine($keys, $arr);
        }
        return $arr;
    }
}