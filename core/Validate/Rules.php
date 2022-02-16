<?php

namespace MkyCore\Validate;

use MkyCore\Exceptions\Validator\RuleNotFoundException;
use Exception;

class Rules
{
    private array $data;
    private array $rules;
    private array $callbacks;
    private array $errors = [];
    const FIELD = 'field.';

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->callbacks = [
            'required' => new Rule(function ($subject) {
                if (empty($subject)) {
                    return false;
                }
                return $subject;
            }, '%s field is required'),
            'minL' => new Rule(function (string $field, $subject) {
                $field = $this->testField($field);
                if (strlen($subject) < (int) $field) {
                    return false;
                }
                return $subject;
            }, '%s field must have at least %s characters'),
            'maxL' => new Rule(function (string $field, $subject) {
                $field = $this->testField($field);
                if (strlen($subject) > (int) $field) {
                    return false;
                }
                return $subject;
            }, '%s field must have at most %s characters'),
            'min' => new Rule(function (string $field, $subject) {
                $field = $this->testField($field);
                if ((float) $subject < (float) $field) {
                    return false;
                }
                return $subject;
            }, '%s field must be greater than %s'),
            'max' => new Rule(function (string $field, $subject) {
                $field = $this->testField($field);
                if ((float) $subject > (float) $field) {
                    return false;
                }
                return $subject;
            }, '%s field must be less than %s'),
            'confirmed' => new Rule(function (string $field, string $subject) {
                if(strpos($field, 'field.') === false){
                    return false;
                }
                $field = $this->testField($field);
                if ($field !== $subject) {
                    return false;
                }
                return $subject;
            }, '%s field must be confirmed by %s'),
            'same' => new Rule(function (string $field, string $subject) {
                $field = $this->testField($field);
                if ($field !== $subject) {
                    return false;
                }
                return $subject;
            }, '%s field must be same as %s'),
            'different' => new Rule(function (string $field, string $subject) {
                $field = $this->testField($field);
                if ($field === $subject) {
                    return false;
                }
                return $subject;
            }, '%s field must be different from %s'),
            'beforeDate' => new Rule(function (string $field, string $subject) {
                if ($field == 'now') {
                    $field = "Y-m-d";
                }
                $field = $this->testField($field);
                if (date($field) < date($subject)) {
                    return false;
                }
                return $subject;
            }, '%s field must be before %s'),
            'afterDate' => new Rule(function (string $field, string $subject) {
                if ($field == 'now') {
                    $field = "Y-m-d H:i:s";
                }
                $field = $this->testField($field);
                if (date($field) > date($subject)) {
                    return false;
                }
                return $subject;
            }, '%s field must be after %s')
        ];
    }

    /**
     * Check if the rule exist in the rule list
     * and run the callable
     *
     * @param string $key
     * @param $subject
     * @return bool
     * @throws RuleNotFoundException
     */
    public function checkRule(string $key, $subject)
    {
        if (isset($this->rules[$key])) {
            $rules = array_filter(explode('|', $this->rules[$key]), function ($r) {
                return $r;
            });
            foreach ($rules as $name => $rule) {
                $ruleArgs = array_filter(explode(':', $rule), function ($r) {
                    return $r;
                });
                $function = array_shift($ruleArgs);
                $ruleArgs[] = $subject;
                if(!isset($this->callbacks[$function])){
                    throw new RuleNotFoundException("Rule $function doesn't exist");
                }
                if (call_user_func_array($this->callbacks[$function]->getCallback(), $ruleArgs) === false) {
                    array_pop($ruleArgs);
                    $ruleArgs = array_map(function ($ra) {
                        if (stripos($ra, self::FIELD) !== false) {
                            $field = str_replace(self::FIELD, '', $ra);
                            $ra = "$field:" . $this->data[str_replace(self::FIELD, '', $ra)];
                        }
                        return $ra;
                    }, $ruleArgs);
                    $this->errors[$key] = sprintf($this->callbacks[$function]->getErrorMessage(), ...[$key, ...$ruleArgs]);
                    return false;
                }
            }
        }
        return $subject;
    }

    /**
     * Check if the field is in form data
     *
     * @param string $field
     * @return mixed|string
     */
    public function testField(string $field)
    {
        if (strpos($field, self::FIELD) === 0) {
            return $this->data[str_replace(self::FIELD, '', $field)];
        }
        return $field;
    }

    /**
     * Return all errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
