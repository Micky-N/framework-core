<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Input;
use MkyCommand\Input\InputArgument;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Str;

class Controller extends Create
{

    protected string $outputDirectory = 'Controllers';
    protected string $createType = 'controller';
    protected string $suffix = 'Controller';

    protected string $description = 'Create a new controller';

    public function settings(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of controller, by default is suffixed by Controller')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the controller')
            ->addOption('crud', 'c', InputOption::NONE, 'Crud methods implementation')
            ->addOption('crud-api', 'a', InputOption::NONE, 'Api Crud methods implementation');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @param ModuleKernel $moduleKernel
     * @param array $vars
     * @return void
     */
    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): void
    {
        $crud = $input->hasOption('crud');
        $crudApi = $input->hasOption('crud-api');
        if ($crud || $crudApi) {
            $name = $vars['name'];
            if ($crud) {
                $vars['crud'] = $this->implementCrud($name);
            } elseif ($crudApi) {
                $vars['crud'] = $this->implementCrudApi($name);
            }
            $vars['head'] = $this->implementHead($name, $this->variables['parent'] ?? '');
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function implementCrud(string $name): string
    {
        $name = Str::singularize(strtolower(str_replace('Controller', '', $name)));
        $param = '{' . $name . '}';
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
        $param = '{' . $name . '}';
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
        $prefix = $this->variables ? Str::pluralize($this->variables['name']) : $name;
        return <<<ROUTER

/**
 * @Router('/$prefix', name: '$name')
 */
ROUTER;
    }
}