<?php

namespace MkyCore\Validate;

use Exception;

class Validator
{

    private array $errors = [];


    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     */
    public function __construct(private readonly array $data, private readonly array $rules, private readonly array $messages = [])
    {
    }

    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    /**
     * Run rules control (instantiate mode)
     *
     * @return bool
     * @throws Exception
     */
    public function passed(): bool
    {
        $rules = new Rules($this->data, $this->rules);
        if (count($this->data) < 1 && count($this->rules) > 0) {
            $this->errors['form'] = 'Form must be filled';
            return false;
        }
        foreach ($this->data as $key => $d) {
            $customMessages = [];
            if ($this->messages) {
                foreach ($this->messages as $name => $message) {
                    if (str_starts_with($name, $key)) {
                        $name = str_replace($key . ':', '', $name);
                        $customMessages[$name] = $message;
                    }
                }
            }
            $rules->checkRule($key, $d, $customMessages);
        }
        if (!empty($rules->getErrors())) {
            $this->errors = $rules->getErrors();
            return false;
        }
        return true;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get data form
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get all rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
