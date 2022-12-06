<?php

namespace MkyCore\Console\Create;

class Middleware extends Create
{
    protected string $outputDirectory = 'Middlewares';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:middleware'],
    ];
    private array $types = ['global', 'module', 'route'];

    protected function handleQuestions(array $replaceParams, array $params = []): array
    {
        $alias = '';
        do {
            $confirm = true;
            $type = $this->sendQuestion('Enter the type of middleware ('.join('/', $this->types).')', 'route') ?: 'route';
            if (!in_array($type, $this->types)) {
                $this->sendError("Wrong middleware type", $type);
                $confirm = false;
            }
        } while (!$confirm);

        if($type === 'route'){
            do {
                $confirm = true;
                $alias = $this->sendQuestion('Enter the middleware alias');
                if (!$alias) {
                    $this->sendError("No alias entered", 'NULL');
                    $confirm = false;
                }
            } while (!$confirm);
        }

        $this->writeInAliasesFile($type, $replaceParams, $alias);
        return $replaceParams;
    }

    private function writeInAliasesFile(string $type, array $replaceParams, string $alias = ''): bool
    {
        $typeMiddlewarefile = $type == 'module' ? 'middlewares' : $type.'Middlewares';
        $module = $replaceParams['module'];
        $name = $replaceParams['name'];
        $class = "\App\\" . ($module ?: '') . "Middlewares\\$name";
        $file = match ($type) {
            'global' => getcwd() . DIRECTORY_SEPARATOR . "app\Middlewares\aliases.php",
            default => getcwd() . DIRECTORY_SEPARATOR . "app\\" . ($module ?: '') . "Middlewares\aliases.php",
        };

        $prefix = '';
        if($alias){
            $prefix = "'$alias' => ";
        }
        $arr = explode("\n", file_get_contents($file));
        $index = array_keys(preg_grep("/'$typeMiddlewarefile' => \[/i", $arr));
        if(!$index){
            $end = count($arr) - 1;
            $virgule = $end - 1;
            if(str_contains($arr[$virgule], ']')){
                $arr[$virgule] = str_replace(']', '],', $arr[$virgule]);
            }
            array_splice($arr, $end, 0, "\t'$typeMiddlewarefile' => [\n\t]");
            $arr = implode("\n", $arr);
            file_put_contents($file, $arr);
            $arr = explode("\n", file_get_contents($file));
            $index = array_keys(preg_grep("/'$typeMiddlewarefile' => \[/i", $arr));
        }
        $middlewaresLine = $index[0];
        array_splice($arr, $middlewaresLine + 1, 0, "\t    $prefix$class::class,");
        $arr = array_values($arr);
        $arr = implode("\n", $arr);
        file_put_contents($file, $arr);
        return true;
    }
}