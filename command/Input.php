<?php

namespace MkyCommand;

use MkyCommand\Exceptions\CommandException;
use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;

class Input
{
    use Color;

    private readonly string $file;
    private readonly string $signature;
    private array $options;
    private array $arguments;
    private array $variables = [];

    public function __construct(array $inputs)
    {
        $this->file = array_shift($inputs);
        if (!$inputs || !$inputs[0]) {
            $inputs = ['help'];
        }
        $this->signature = array_shift($inputs);
        $this->options = $this->parseInputsToOptions($inputs);
        $this->arguments = $this->parseInputsToArguments($inputs);
    }

    private function parseInputsToOptions(array &$inputs): array
    {
        if ($inputs) {
            $options = [];
            for ($i = 0; $i < count($inputs); $i++) {
                if (!in_array($i, $inputs)) {
                    $resOption = null;
                    $input = $inputs[$i];
                    if (str_starts_with($input, '--') && $input[2] !== '-') {
                        $explodeInputs = explode('=', $input);
                        $key = str_replace('--', '', trim($explodeInputs[0]));
                        $resOption = !empty($explodeInputs[1]) ? trim($explodeInputs[1]) : false;
                    } elseif (str_starts_with($input, '-') && $input[1] !== '-' && strlen($input) == 2) {
                        $key = $input[1];
                        if (array_key_exists($i + 1, $inputs) && !str_starts_with($inputs[$i + 1], '-') && !str_contains($inputs[$i + 1], '=')) {
                            $resOption = trim($inputs[$i + 1]);
                            array_splice($inputs, $i + 1, 1);
                        } else {
                            $resOption = false;
                        }
                    } elseif (str_starts_with($input, '-') && $input[1] !== '-' && strlen($input) > 2) {
                        $key = $input[1];
                        $value = str_replace("-$key", '', $input);
                        $resOption = $value;
                    } else {
                        continue;
                    }
                    if (!array_key_exists($key, $options)) {
                        $options[$key] = is_numeric($resOption) ? (int)$resOption : $resOption;
                    } else {
                        $resOption = is_numeric($resOption) ? (int)$resOption : $resOption;
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
        if ($inputs) {
            $options = [];
            for ($i = 0; $i < count($inputs); $i++) {
                if (!empty($inputs[$i])) {
                    $input = $inputs[$i];
                    if (!str_starts_with($input, '-')) {
                        if (str_contains($input, '=')) {
                            $explodeInputs = explode('=', $input);
                            $options[trim($explodeInputs[0])] = is_numeric(trim($explodeInputs[1])) ? (int)trim($explodeInputs[1]) : trim($explodeInputs[1]);
                        } else {
                            $options[$i] = is_numeric($input) ? (int)$input : $input;
                        }
                    }
                }
            }
            return $options;
        }
        return [];
    }

    public static function prompt()
    {
        $stdin = fopen('php://stdin', 'r');
        if (false === $stdin) {
            throw new \RuntimeException('Failed to open STDIN, could not prompt user for input.');
        }
        $answer = self::trimAnswer(fgets($stdin, 4096));
        fclose($stdin);

        return $answer;
    }

    private static function trimAnswer($str)
    {
        return preg_replace('{\r?\n$}D', '', (string)$str) ?: '';
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
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return Input
     */
    public function addVariable(string $name, mixed $value): static
    {
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasVariable(string $name): bool
    {
        return isset($this->variables[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function removeVariable(string $name): mixed
    {
        $value = $this->arguments[$name];
        unset($this->arguments[$name]);
        return $value;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws CommandException
     */
    public function getArgument(string $name): mixed
    {
        if (!$this->hasArgument($name)) {
            throw CommandException::ArgumentNotFound($name);
        }
        $argument = $this->arguments[$name];
        if (is_null($argument)) {
            return null;
        }
        if ($argument instanceof InputArgument) {
            return $argument->getValue();
        }
        return $argument;
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
     * @throws CommandException
     */
    public function getOption(string $name)
    {
        if (!$this->hasOption($name)) {
            throw CommandException::OptionNotFound($name);
        }
        $options = $this->options[$name];
        if (is_null($options)) {
            return null;
        }
        if ($options instanceof InputOption) {
            return $options->getValue();
        }
        return $options;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function ask(string $question, string $default = ''): string
    {
        $message = "\n" . $this->coloredMessage($question, 'blue', 'bold');
        if ($default) {
            $message .= $this->coloredMessage(" [$default]", 'light_yellow');
        }
        $message .= ":\n";
        echo $message;
        return trim((string)readline("> "));
    }

    public function askMultiple(string $question, array $choices, int $defaultIndex = null): string
    {
        $message = "\n" . $this->coloredMessage($question, 'blue', 'bold');
        if ($defaultIndex) {
            $message .= $this->coloredMessage(" [$defaultIndex]", 'light_yellow');
        }
        $message .= ":\n";
        echo $message;
        return trim((string)readline("> "));
    }

    public function password(): string
    {

    }

    public function confirm(string $message, bool $default): bool
    {
        return $default;
    }
}