<?php

namespace MkyCommand;

use Closure;
use MkyCommand\Exceptions\CommandException;
use MkyCommand\Exceptions\InputArgumentException;
use MkyCommand\Exceptions\InputOptionException;
use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;

class Input
{
    use Color;

    private readonly string $file;
    private readonly string $signature;
    private array $options;
    private array $arguments;

    public function __construct(array $inputs = [])
    {
        $this->file = $inputs ? array_shift($inputs) : '';
        if (!$inputs || !$inputs[0]) {
            $inputs = ['help'];
        }
        $this->signature = $inputs ? array_shift($inputs) : '';
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
    public function options(): array
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
    public function arguments(): array
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
     * @return mixed
     * @throws InputArgumentException
     */
    public function argument(string $name): mixed
    {
        if (!$this->hasArgument($name)) {
            throw InputArgumentException::ArgumentNotFound($name);
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
     * @param string $name
     * @return mixed|null
     * @throws InputOptionException
     */
    public function option(string $name): mixed
    {
        if (!$this->hasOption($name)) {
            throw InputOptionException::OptionNotFound($name);
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

    public function choice(string $question, array $choices, int $defaultIndex = null, ?int $maxAttempts = null, string|Closure $errorMessage = ''): string
    {
        $message = "\n" . $this->coloredMessage($question, 'blue', 'bold');
        if (!is_null($defaultIndex)) {
            $message .= $this->coloredMessage(" [$choices[$defaultIndex]]", 'light_yellow');
        }
        $message .= ":\n";
        echo $message;
        $table = new ConsoleTable();
        $table->hideBorder();
        for ($i = 0; $i < count($choices); $i++) {
            $table->addRow(["[$i]", $choices[$i]]);
        }
        $table->display();
        $answer = trim((string)readline("> ")) ?: false;
        if ($answer !== false) {
            if (isset($choices[$answer])) {
                return $choices[$answer];
            } else {
                if (is_callable($errorMessage)) {
                    $errorMessage = $errorMessage($answer, $choices);
                }
                if (!is_string($errorMessage)) {
                    $errorMessage = '';
                }
                if ($maxAttempts && $maxAttempts > 1) {
                    $maxAttempts--;
                    if ($errorMessage) {
                        echo "\n$errorMessage\n";
                    }
                    $this->choice($question, $choices, $defaultIndex, $maxAttempts);
                } else {
                    $this->error("Value is not correct, the number of attempts is exceeded");
                    exit();
                }
            }
        }
        return $choices[$defaultIndex];
    }

    public function confirm(string $message, bool $default = false): bool
    {
        $answer = $this->ask($message . ' (y/n)', $default ? 'y' : 'n');
        if (!$answer) {
            return $default;
        }
        if (!in_array(strtolower($answer), ['y', 'n'])) {
            $this->error('Value not correct');
            exit();
        }
        return strtolower($answer) === 'y';
    }

    public function ask(string $question, string $default = '', string $defaultMessage = ''): string
    {
        $message = "\n" . $this->coloredMessage($question, 'blue', 'bold');
        if ($default) {
            $message .= $this->coloredMessage(' ['.($defaultMessage ?: $default).']', 'light_yellow');
        }
        $message .= ":\n";
        echo $message;
        return trim((string)readline("> ")) ?: $default;
    }
}