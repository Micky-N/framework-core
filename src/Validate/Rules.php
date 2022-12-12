<?php

namespace MkyCore\Validate;

use Exception;
use MkyCore\Exceptions\Validator\RuleNotFoundException;
use MkyCore\Exceptions\Validator\RuleParamNotDeclareException;
use MkyCore\Exceptions\Validator\RuleParamNotLogicException;
use MkyCore\File;
use MkyCore\Interfaces\RuleInterface;
use ReflectionException;
use ReflectionFunction;

class Rules
{
    const FIELD = 'field.';
    private array $data;
    private array $rules;
    private array $callbacks;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->callbacks = [
            'required' => new Rule(function ($value) {
                return empty($value) ? false : $value;
            }, '{field} field is required'),
            'minL' => new Rule(function (string $param, string $value) {
                if (!is_numeric($param)) {
                    throw new RuleParamNotLogicException("Param must be integer");
                }
                return strlen($value) < (int)$param ? false : $value;
            }, '{field} field must have at least {param} characters'),
            'maxL' => new Rule(function (string $param, string $value) {
                if (!is_numeric($param)) {
                    throw new RuleParamNotLogicException("Param must be integer");
                }
                return strlen($value) > (int)$param ? false : $value;
            }, '{field} field must have at most {param} characters'),
            'min' => new Rule(function (string $param, string|int|float $value) {
                if (!is_numeric($param)) {
                    throw new RuleParamNotLogicException("Param must be integer ot float");
                }
                return (float)$value < (float)$param ? false : $value;
            }, '{field} field must be greater than {param}'),
            'between' => new Rule(function (string $param, string|int|float $value) {
                $params = explode(',', $param);
                if (count($params) !== 2) {
                    throw new RuleParamNotLogicException("There must be 2 params");
                }
                $params = array_map(function ($param) {
                    if (!is_numeric(trim($param))) {
                        throw new RuleParamNotLogicException("Param must be integer ot float");
                    }
                    return (float)trim($param);
                }, $params);
                $min = $params[0];
                $max = $params[1];
                return $min > (float)$value || (float)$value > $max ? false : $value;
            }, '{field} field must be between [{param}]'),
            'max' => new Rule(function (string $param, string|int|float $value) {
                if (!is_numeric($param)) {
                    throw new RuleParamNotLogicException("Param must be integer ot float");
                }
                return (float)$value > (float)$param ? false : $value;
            }, '{field} field must be less than {param}'),
            'confirmed' => new Rule(function (string $param, string $value, array $data) {
                return !isset($data[$param]) || $data[$param] !== $value ? false : $value;
            }, '{field} field must be confirmed by {param}'),
            'same' => new Rule(function (string $param, string $value) {
                return $param !== $value ? false : $value;
            }, '{field} field must be same as {param}'),
            'different' => new Rule(function (string $param, string $value) {
                return $param === $value ? false : $value;
            }, '{field} field must be different from {param}'),
            'starts_with' => new Rule(function (string $param, string $value) {
                return !str_starts_with($value, $param) ? false : $value;
            }, '{field} field must start with {param}'),
            'ends_with' => new Rule(function (string $param, string $value) {
                return !str_ends_with($value, $param) ? false : $value;
            }, '{field} field must end with {param}'),
            'contains' => new Rule(function (string $param, string $value) {
                return !str_contains($value, $param) ? false : $value;
            }, '{field} field must contain {param}'),
            'before' => new Rule(function (string $param, string $value) {
                $param = $this->getDateFromString($param);
                return date($param) < date($value) ? false : $value;
            }, '{field} field must be before {param}'),
            'after' => new Rule(function (string $param, string $value) {
                $param = $this->getDateFromString($param);
                return date($param) > date($value) ? false : $value;
            }, '{field} field must be after {param}'),
            'in' => new Rule(function (string $param, string $value) {
                $array = array_map(fn($f) => trim($f), explode(',', $param));
                return !in_array($value, $array) ? false : $value;
            }, '{field} field must be in {param}'),
            'notIn' => new Rule(function (string $param, string $value) {
                $array = array_map(fn($f) => trim($f), explode(',', $param));
                return in_array($value, $array) ? false : $value;
            }, '{field} field must not be in {param}'),
            'enum' => new Rule(function (string $param, string $value) {
                if (!enum_exists($param)) {
                    throw new Exception("Enum $param doesn't exist");
                }
                /** @var UnitEnum $param */
                return !$param::tryFrom($value) ? false : $value;
            }, '{field} field must be in enum {param}'),
            'max_size' => new Rule(function (string $param, File $value) {
                return (int)$param < $value->getSize() * 1000 ? false : $value;
            }, 'the size of the {field} file must be less than {param}Ko'),
            'min_size' => new Rule(function (string $param, File $value) {
                return (int)$param > $value->getSize() * 1000 ? false : $value;
            }, 'the size of the {field} file must be greater than {param}Ko'),
            'type' => new Rule(function (string $param, File $value) {
                $exts = array_map(fn($ext) => trim($ext), explode(',', $param));
                return !in_array($value->extension(), $exts) ? false : $value;
            }, 'the type of the {field} file must in [{param}]'),
        ];
    }

    /**
     * @param string $param
     * @return string
     */
    private function getDateFromString(string $param): string
    {
        if ($param == 'now') {
            $param = "Y-m-d H:i:s";
        } elseif ($param == 'tomorrow') {
            $param = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s"));
            $param = $param->addDay()->format('Y-m-d H:i:s');
        } elseif ($param == 'yesterday') {
            $param = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", date("Y-m-d H:i:s"));
            $param = $param->subDay()->format('Y-m-d H:i:s');
        }
        return $param;
    }

    /**
     * Check if the rule exist in the rule list
     * and run the callable
     *
     * @param string $key
     * @param mixed $value
     * @param array $customMessages
     * @return bool
     * @throws ReflectionException
     * @throws RuleNotFoundException
     * @throws RuleParamNotDeclareException
     */
    public function checkRule(string $key, mixed $value, array $customMessages = []): mixed
    {
        if (isset($this->rules[$key])) {
            $rules = array_filter((array)$this->rules[$key], function ($r) {
                return $r;
            });
            foreach ($rules as $name => $rule) {
                $ruleClass = false;
                $param = null;
                if (is_string($rule)) {
                    $ruleArgs = array_filter(explode(':', $rule, 2), function ($r) {
                        return $r;
                    });
                    $function = array_shift($ruleArgs);
                    if (!isset($this->callbacks[$function])) {
                        throw new RuleNotFoundException("Rule $function doesn't exist");
                    }
                    $param = $ruleArgs[0] ?? null;
                    $ruleClass = $this->callbacks[$function];
                }
                if ($rule instanceof RuleInterface) {
                    $rule = $rule->make();
                    if (method_exists($rule, 'getParam')) {
                        $param = $rule->getParam();
                    }
                    $ruleClass = $rule;
                }
                if ($param) {
                    $param = $this->testFieldParam($param);
                }

                $callbackReflection = new ReflectionFunction($ruleClass->getCallback());
                $parameters = $callbackReflection->getParameters();
                $ruleArgs = [];
                foreach ($parameters as $parameter) {
                    if ($parameter->name == 'param') {
                        $ruleArgs[$parameter->name] = $param;
                    } elseif ($parameter->name == 'value') {
                        $ruleArgs[$parameter->name] = $value;
                    } elseif ($parameter->name == 'data') {
                        $ruleArgs[$parameter->name] = $this->data;
                    }
                }
                $callback = call_user_func_array($ruleClass->getCallback(), $ruleArgs);

                if ($callback === false) {
                    array_pop($ruleArgs);
                    $this->errors[$key] = $this->parseErrorMessage(isset($function) && isset($customMessages[$function]) ? $customMessages[$function] : $ruleClass->getErrorMessage(), $key, $value, $ruleArgs);
                    return false;
                }
            }
        }
        return $value;
    }

    /**
     * Check if the field is in form data
     *
     * @param string $param
     * @return mixed|string
     */
    public function testFieldParam(string $param): mixed
    {
        if (str_starts_with($param, self::FIELD)) {
            return $this->data[str_replace(self::FIELD, '', $param)];
        }
        return $param;
    }

    /**
     * @param string $message
     * @param string $key
     * @param mixed $value
     * @param array $ruleArgs
     * @return string
     * @throws RuleParamNotDeclareException
     */
    private function parseErrorMessage(string $message, string $key, mixed $value, array $ruleArgs): string
    {
        $params = ['field' => $key, 'value' => $value, ...$ruleArgs];
        return preg_replace_callback('/{(.*?)}/', function ($expression) use ($params) {
            $res = array_shift($expression);
            if (isset($expression[0])) {
                $expression = $expression[0];
                $param = $params[$expression];
                if (empty($param)) {
                    throw new RuleParamNotDeclareException("Param $expression is not declare");
                }
                $res = $param;
            }
            return $res;
        }, $message);
    }

    /**
     * Return all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
