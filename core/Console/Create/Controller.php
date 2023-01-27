<?php

namespace MkyCore\Console\Create;

use MkyCommand\AbstractCommand;
use MkyCommand\Input;
use MkyCommand\Input\InputOption;
use MkyCommand\Output;
use MkyCore\Application;
use MkyCore\File;
use MkyCore\Str;

class Controller extends AbstractCommand
{

    public function __construct(private readonly Application $application, private readonly array $moduleOptions = [])
    {
    }

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of controller, by default is suffixed by Controller')
            ->addOption('real', 'r', InputOption::NONE, 'Keep the real name of the controller')
            ->addOption('crud', 'c', InputOption::NONE, 'Crud methods implementation')
            ->addOption('crud-api', 'a', InputOption::NONE, 'Api Crud methods implementation');
    }

    public function execute(Input $input, Output $output): mixed
    {
        $fileModel = file_get_contents(dirname(__DIR__) . '/models/controller.model');
        $name = $input->argument('name');
        $name = $input->option('real') ? ucfirst($name) : ucfirst($name) . 'Controller';

        if ($this->application->getModules()) {
            $allModules = array_keys($this->application->getModules());
            $moduleIndex = $input->choice('Which module you want to create controller', $allModules, 0, 3, "Module not found");
            $module = $this->application->getModuleKernel($moduleIndex);
        } else {
            $module = $this->application->getModuleKernel('root');
        }
        $namespace = $module->getModulePath(true);
        $namespace = $namespace . '\\' . $name;
        $crud = $input->hasOption('crud') || in_array('--crud', $this->moduleOptions);
        $crudApi = $input->hasOption('crud-api') || in_array('--crud-api', $this->moduleOptions);
        if ($crud || $crudApi) {
            if ($crud) {
                $replaceParams['crud'] = $this->implementCrud($name);
            } elseif ($crudApi) {
                $replaceParams['crud'] = $this->implementCrudApi($name);
            }
            $replaceParams['head'] = $this->implementHead($name, $replaceParams['parent'] ?? '');
        }
        $outputDir = File::makePath([$module->getModulePath(), 'Controllers']);
        if (file_exists($outputDir . DIRECTORY_SEPARATOR . $name . '.php')) {
            $output->error("$name file already exists", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
            exit();
        }

        if (!is_dir($outputDir)) {
            mkdir($outputDir, '0777', true);
        }

        file_put_contents($outputDir . DIRECTORY_SEPARATOR . $name . '.php', $fileModel);
        if(count($this->moduleOptions) > 0){
            return $name;
        }else{
            $output->success("$name created", $outputDir . DIRECTORY_SEPARATOR . $name . '.php');
            return self::SUCCESS;
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
        $prefix = $this->moduleOptions ? Str::pluralize($this->moduleOptions['name']) : $name;
        return <<<ROUTER

/**
 * @Router('/$prefix', name: '$name')
 */
ROUTER;
    }

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
}