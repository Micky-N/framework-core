<?php

namespace MkyCore\Console\Create;

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
     * @return string
     */
    private function implementCrud(string $name): string
    {
        $name = strtolower(str_replace('Controller', '', $name));
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
     * @Router('/{id}', name:'show', methods:['GET'])
     * Display the specified resource.
     *
     * @param  int|string \$$name
     */
    public function show(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/{id}/edit', name:'edit', methods:['GET'])
     * Show the form for editing the specified resource.
     *
     * @param  int|string \$$name
     */
    public function edit(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/{id}', name:'update', methods:['PUT'])
     * Update the specified resource in storage.
     *
     * @param  int|string \$$name
     */
    public function update(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/{id}', name:'destroy', methods:['DELETE'])
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
     * @return string
     */
    private function implementCrudApi(string $name): string
    {
        $name = strtolower(str_replace('Controller', '', $name));
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
     * @Router('/{id}', name:'show', methods:['GET'])
     * Display the specified resource.
     *
     * @param  int|string \$$name
     */
    public function show(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/{id}', name:'update', methods:['PUT'])
     * Update the specified resource in storage.
     *
     * @param  int|string \$$name
     */
    public function update(int|string \$$name)
    {
        //
    }

    /**
     * @Router('/{id}', name:'destroy', methods:['DELETE'])
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
        $name = $parent ? "$parent.$name" : $name;
        $prefix = $this->moduleOptions ? '' : $name;
        return <<<ROUTER

/**
 * @Router('/$prefix', name: '$name')
 */
ROUTER;
    }
}