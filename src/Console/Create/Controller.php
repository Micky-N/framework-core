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
        if (in_array('--crud', $params) || in_array('--crud', $this->moduleOptions)) {
            if (method_exists($this, 'implementCrud')) {
                $replaceParams['crud'] = $this->implementCrud();
                $replaceParams['head'] = $this->implementHead($replaceParams['name']);
            }
        }
        return $replaceParams;
    }

    /**
     * @return string
     */
    private function implementCrud(): string
    {
        return <<<CRUD
/**
     * @Router('/', name:'index', methods:['GET'])
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        //
    }

    /**
     * @Router('/create', name:'create', methods:['GET'])
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create()
    {
        //
    }

    /**
     * @Router('/', name:'store', methods:['POST'])
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse
     */
    public function store()
    {
        //
    }

    /**
     * @Router('/{id}', name:'show', methods:['GET'])
     * Display the specified resource.
     *
     * @param  int|string \$id
     * @return View
     */
    public function show(int|string \$id)
    {
        //
    }

    /**
     * @Router('/{id}/edit', name:'edit', methods:['GET'])
     * Show the form for editing the specified resource.
     *
     * @param  int|string \$id
     * @return View
     */
    public function edit(int|string \$id)
    {
        //
    }

    /**
     * @Router('/{id}', name:'update', methods:['PUT'])
     * Update the specified resource in storage.
     *
     * @param  int|string \$id
     * @return RedirectResponse
     */
    public function update(int|string \$id)
    {
        //
    }

    /**
     * @Router('/{id}', name:'destroy', methods:['DELETE'])
     * Remove the specified resource from storage.
     *
     * @param int|string \$id
     * @return RedirectResponse
     */
    public function destroy(int|string \$id)
    {
        //
    }
CRUD;
;
    }

    private function implementHead(string $name): string
    {
        $name = strtolower(str_replace('Controller', '', $name));
        $prefix = $this->moduleOptions ? '' : $name;
        return <<<ROUTER

/**
 * @Router('/$prefix', name: '$name')
 */
ROUTER;
    }
}