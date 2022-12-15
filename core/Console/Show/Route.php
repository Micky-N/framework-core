<?php

namespace MkyCore\Console\Show;

use MkyCore\Console\Color;
use MkyCore\Facades\Router;

class Route extends Show
{

    const HEADERS = [
        'getMethods' => 'Request Method',
        'getUrl' => 'Url',
        'getAction' => 'Controller',
        'getName' => 'Name',
        'getModule' => 'Module'
    ];

    const FILTERS = ['controller', 'module', 'name', 'url', 'methods'];

    use Color;

    public function process(): bool|string
    {
        $print = in_array('--print', $this->params);
        $table = new ConsoleTable();
        $table->setHeaders(array_keys(self::HEADERS));
        $filters = $this->getFilters($this->parseParams());
        $routes = Router::getRoutes($filters);
        if(!$routes){
            echo $this->getColoredString('No route found with these filter criteria', 'red', 'bold').":\n";
            foreach ($filters as $cr => $value){
                echo "  - $cr: ".join('|', (array) $value)."\n";
            }
            return false;
        }
        $methods = array_keys(self::HEADERS);
        for ($i = 0; $i < count($routes); $i++) {
            $route = $routes[$i];
            $array = [];
            foreach ($methods as $method) {
                if ($method == 'getAction') {
                    $array[] = join('::', $route->{$method}());
                } elseif ($method == 'getMethods') {
                    $array[] = $this->parseMethods(join('|', $route->{$method}()), $print);
                } elseif ($method == 'getUrl') {
                    $array[] = '/' . trim($this->parseUrl($route->{$method}(), $print), '/');
                } else {
                    $array[] = $route->{$method}();
                }
            }
            $table->addRow($array);
        }
        if ($print) {
            echo "List of routes:\n";
        }

        $table->setPadding(2)
            ->setIndent(2)
            ->showAllBorders()
            ->display();
        return true;
    }

    private function getFilters(array $inputs): array
    {
        $filters = [];
        $filtersBase = self::FILTERS;
        for($i = 0; $i < count($filtersBase); $i ++){
            $filterBase = $filtersBase[$i];
            if(array_key_exists("--$filterBase", $inputs)){
                $value = $inputs["--$filterBase"];
                if($filterBase == 'methods'){
                    $value = explode('!', $value);
                }
                $filters[$filterBase] = $value;
            }
        }
        return $filters;
    }

    private function parseMethods(string $methods, bool $print = false): string
    {
        foreach (['POST' => 'green', 'GET' => 'blue', 'PUT' => 'light_purple', 'DELETE' => 'red'] as $method => $color) {
            $apply = $print ? $method : $this->getColoredString($method, $color);
            $methods = str_replace($method, $apply, $methods);
        }
        return $methods;
    }

    private function parseUrl(string $url, bool $print = false): string
    {
        $apply = $print ? '$1' : $this->getColoredString('$1', 'light_yellow', 0);
        return preg_replace('/(\{.*?})/', $apply, $url);
    }
}