<?php

namespace MkyCore\Console\Create;

use MkyCore\Str;

class Controller extends Create
{
    protected string $outputDirectory = 'Controllers';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:controller'],
    ];

    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        $replaceParams['crud'] = '';
        $replaceParams['head'] = '';
        $name = $replaceParams['name'];
        $crud = in_array('--crud', $params) || in_array('--crud', $this->moduleOptions);
        $crudApi = in_array('--crud-api', $params) || in_array('--crud-api', $this->moduleOptions);
        if ($crud || $crudApi) {
            if ($crud) {
                $replaceParams['crud'] = $this->implementCrud($name);
            } elseif ($crudApi) {
                $replaceParams['crud'] = $this->implementCrudApi($name);
            }
            $replaceParams['head'] = $this->implementHead($name, $replaceParams['parent'] ?? '');
        }
        return $replaceParams;
    }

    /**
     * @param string $name
     * @return string
     */
    private function implementCrud(string $name): string
    {
        $name = Str::singularize(strtolower(str_replace('Controller', '', $name)));
        $param = '{'.$name.'}';
        return <<<CRUD
/**
     * @Router('/', name:'index', methods:['GET'])
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        //
    }

    /**
     * @Router('/create', name:'create', methods:['GET'])
     * Show the form for creating a new resource.
     *
     */
    public function create()
    {
        //
    }

    /**
     * @Router('/', name:'store', methods:['POST'])
     * Store a newly created resource in storage.
     *
     */
    public function store()
    {
        //
    }

    /**
     * @Router('/$param', name:'show', methods:['GET'])
     * Display the specified resource.
     *
     * @param  int|string \$$name
     */
    public function show(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/$param/edit', name:'edit', methods:['GET'])
     * Show the form for editing the specified resource.
     *
     * @param  int|string \$$name
     */
    public function edit(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/$param', name:'update', methods:['PUT'])
     * Update the specified resource in storage.
     *
     * @param  int|string \$$name
     */
    public function update(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/$param', name:'destroy', methods:['DELETE'])
     * Remove the specified resource from storage.
     *
     * @param int|string \$$name
     */
    public function destroy(int|string \$$name)
    {
        //
    }
CRUD;
    }

    /**
     * @param string $name
     * @return string
     */
    private function implementCrudApi(string $name): string
    {
        $name = Str::singularize(strtolower(str_replace('Controller', '', $name)));
        return <<<CRUD
/**
     * @Router('/', name:'index', methods:['GET'])
     * Display a listing of the resource.
     *
     */
    public function index()
    {
        //
    }

    /**
     * @Router('/', name:'store', methods:['POST'])
     * Store a newly created resource in storage.
     *
     */
    public function store()
    {
        //
    }

    /**
     * @Router('/$param', name:'show', methods:['GET'])
     * Display the specified resource.
     *
     * @param  int|string \$$name
     */
    public function show(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/$param', name:'update', methods:['PUT'])
     * Update the specified resource in storage.
     *
     * @param  int|string \$$name
     */
    public function update(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/$param', name:'destroy', methods:['DELETE'])
     * Remove the specified resource from storage.
     *
     * @param int|string \$$name
     */
    public function destroy(int|string \$$name)
    {
        //
    }
CRUD;
    }

    private function implementHead(string $name, string $parent = ''): string
    {
        $name = strtolower(str_replace('Controller', '', $name));
        $name = Str::pluralize($name);
        $parent = Str::pluralize($parent);
        $name = $parent ? "$parent.$name" : $name;
        $prefix = $this->moduleOptions ? Str::pluralize($this->moduleOptions['name']) : $name;
        return <<<ROUTER

/**
 * @Router('/$prefix', name: '$name')
 */
ROUTER;
    }
}