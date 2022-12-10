<?php

namespace MkyCore\Console\Show;

use MkyCore\Console\Color;
use MkyCore\Facades\Router;

class Route
{

    use Color;

    public function __construct(protected readonly array $params = [], protected array $moduleOptions = [])
    {
    }

    public function process()
    {
        $print = in_array('--print', $this->params);
        $headers = [
            'getMethods' => 'Request Method',
            'getUrl' => 'Url',
            'getAction' => 'Controller',
            'getName' => 'Name',
            'getModule' => 'Module'
        ];
        $table = new ConsoleTable();
        $table->setHeaders(array_values($headers));
        $routes = Router::getRoutes();
        $methods = array_keys($headers);
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
        if($print){
            echo "List of routes:\n";
        }
        
        $table->setPadding(2)
            ->setIndent(2)
            ->showAllBorders()
            ->display();
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