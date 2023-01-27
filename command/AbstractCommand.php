<?php

namespace MkyCommand;

use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;

abstract class AbstractCommand
{

    const SUCCESS = 1;
    const ERROR = 2;
    protected string $signature = '';

    /**
     * @var InputArgument[]
     */
    protected array $arguments = [];

    /**
     * @var InputOption[]
     */
    protected array $options = [];

    protected string $description = '';
    protected bool $helpMode = false;

    abstract public function execute(Input $input, Output $output): mixed;

    public function settings(): void
    {

    }

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
                if (in_array($commandArgument->getType(), [InputArgument::REQUIRED, InputArgument::ARRAY | InputArgument::REQUIRED])) {
                    throw CommandException::ArgumentNotFound($name);
                }
                $inputArguments[$index] = false;
            } else {
                if (in_array($commandArgument->getType(), [InputArgument::ARRAY, InputArgument::ARRAY | InputArgument::REQUIRED, InputArgument::ARRAY | InputArgument::OPTIONAL])) {
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
     */
    private function getLongInputOptionCommand(array $inputOptions, int|string $name, InputOption $commandOption): array
    {
        if (isset($inputOptions[$commandOption->getShortname()])) {
            return $inputOptions;
        }
        if (!array_key_exists($name, $inputOptions)) {
            if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::ARRAY | InputOption::OPTIONAL, InputOption::REQUIRED, InputOption::ARRAY | InputOption::REQUIRED])) {
                $inputOptions[$name] = $commandOption->hasDefault() ? $commandOption->getDefault() : null;
            } else if ($commandOption->getType() === InputOption::NEGATIVE && array_key_exists("no-$name", $inputOptions)) {
                $inputOptions = $this->replaceKey($inputOptions, "no-$name", $name);
            } else if ($commandOption->getType() === InputOption::NEGATIVE) {
                $inputOptions[$name] = null;
            } else if ($commandOption->getType() === InputOption::NONE) {
                $inputOptions[$name] = false;
            }
        } else if (!$inputOptions[$name]) {
            if (in_array($commandOption->getType(), [InputOption::NONE, InputOption::NEGATIVE])) {
                $inputOptions[$name] = true;
            } else if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else {
                $inputOptions[$name] = false;
            }
        } else {
            if (in_array($commandOption->getType(), [InputOption::ARRAY | InputOption::REQUIRED, InputOption::ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = (array)$inputOptions[$name];
            }
        }
        return $inputOptions;
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
            if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = null;
            } else if ($commandOption->getType() == InputOption::NONE) {
                $inputOptions[$name] = false;
            } else if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else if (in_array($commandOption->getType(), [InputOption::REQUIRED, InputOption::ARRAY | InputOption::REQUIRED])) {
                throw CommandException::OptionNotFound($name);
            }
        } else if (!$inputOptions[$shortOption]) {
            if ($commandOption->hasDefault()) {
                $inputOptions[$name] = $commandOption->getDefault();
            } else if (in_array($commandOption->getType(), [InputOption::OPTIONAL, InputOption::ARRAY | InputOption::OPTIONAL])) {
                $inputOptions[$name] = null;
            } else if ($commandOption->getType() == InputOption::NONE) {
                $inputOptions[$name] = true;
            }
        } else {
            $inputOptions[$name] = $inputOptions[$shortOption];
            unset($inputOptions[$shortOption]);
        }
        return $inputOptions;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isHelpMode(): bool
    {
        return $this->helpMode;
    }

    public function setHelpMode(): void
    {
        $this->helpMode = true;
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

    public function displayHelp(Input $input, Output $output): int
    {
        $section = $output->section();
        $section->text("Help for Mky CLI Command")->newLine();
        $arguments = $this->getArguments() ? array_values($this->getArguments()) : [];
        $options = $this->getOptions() ? array_values($this->getOptions()) : [];
        $section->text($output->coloredMessage('Command: ' . $input->getFile(), 'gray') . ' ' . $output->coloredMessage($this->getSignature(), 'light_yellow') . "\n")
            ->newLine()
            ->text($output->coloredMessage('Description:', 'blue'))
            ->newLine()
            ->text("  " . $this->getDescription())
            ->newLine(2)
            ->text($output->coloredMessage('Arguments:', 'yellow'))
            ->newLine();
        $table = $output->table();
        for ($i = 0; $i < count($arguments); $i++) {
            $argument = $arguments[$i];
            $table->addRow([$this->getInputType($argument, $output), $argument->getDescription(), '']);
        }

        for ($j = 0; $j < count($options); $j++) {
            $option = $options[$j];
            $default = '';
            if ($option->hasDefault()) {
                $default = $option->getDefault();
                $default = is_array($default) ? '[' . join(', ', $default) . ']' : $default;
            }
            $table->addRow([$this->getInputType($option, $output), $option->getDescription(), $default]);
        }

        $section->text($table->setIndent(1)
            ->hideBorder()
            ->getTable());

        $section->read(false);
        return self::SUCCESS;
    }

    private function getInputType(InputArgument|InputOption $input, Output $output): string
    {
        $type = $input instanceof InputOption ? $this->getOptionType($input) : $this->getArgumentType($input);
        $text = '';
        if ($input instanceof InputOption) {
            $text .= $input->hasShortName() ? '-' . $input->getShortname() . '|' : '';
        }
        $text .= $input->getName() . " [$type]";
        return $output->coloredMessage($text, 'green');
    }

    private function getOptionType(InputOption $option): string
    {
        $type = '';
        $optionType = $option->getType();
        if ($optionType === InputOption::REQUIRED) {
            $type = 'required';
        } else if ($optionType === (InputOption::ARRAY | InputOption::REQUIRED)) {
            $type = 'array|required';
        } else if ($optionType === InputOption::OPTIONAL) {
            $type = 'optional';
        } else if ($optionType === (InputOption::ARRAY | InputOption::OPTIONAL)) {
            $type = 'array|optional';
        } else if ($optionType === InputOption::NONE) {
            $type = 'none_value';
        } else if ($optionType === InputOption::NEGATIVE) {
            $type = 'negative_value';
        }
        return $type;
    }

    private function getArgumentType(InputArgument $argument): string
    {
        $type = '';
        $argumentType = $argument->getType();
        if ($argumentType === InputArgument::REQUIRED) {
            $type = 'required';
        } else if ($argumentType === (InputArgument::ARRAY | InputArgument::REQUIRED)) {
            $type = 'array|required';
        } else if ($argumentType === InputArgument::OPTIONAL) {
            $type = 'optional';
        } else if ($argumentType === (InputArgument::ARRAY | InputArgument::OPTIONAL)) {
            $type = 'array|optional';
        }

        return $type;
    }
}