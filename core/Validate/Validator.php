<?php

namespace MkyCore\Validate;

use MkyCore\Facades\Route;
use MkyCore\Router;
use Exception;

class Validator
{

    private array $data;
    private array $rules;
    private array $errors = [];


    /**
     * @param array $data
     * @param array $rules
     */
    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
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
        if(count($this->data) < 1 && count($this->rules) > 0){
            $this->errors['form'] = 'Form must be filled';
            return false;
        }
        foreach ($this->data as $key => $d) {
            $rules->checkRule($key, $d);
        }
        if (!empty($rules->getErrors())) {
            $this->errors = $rules->getErrors();
            return false;
        }
        return true;
    }

    /**
     * Run rules control and back redirection
     * if error
     *
     * @param array $data
     * @param array $rules
     * @return array|Router
     * @throws Exception
     */
    public static function check(array $data, array $rules)
    {
        $rules = new Rules($data, $rules);
        if(empty($data) && !empty($rules)){
            return Route::back()->withError(['form' => 'Form must be filled']);
        }
        foreach ($data as $key => $d) {
            $data[$key] = $rules->checkRule($key, $d);
        }
        if (!empty($rules->getErrors())) {
            return Route::back()->withError($rules->getErrors());
        }
        return $data;
    }

    /**
     * Get data form
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Get all rules
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
