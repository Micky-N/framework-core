<?php

namespace MkyCore\Console\Show;

use MkyCore\Console\Color;
use MkyCore\Facades\Router;

class Route
{

    use Color;

    protected string|false $createType;

    public function __construct(protected readonly array $params = [], protected array $moduleOptions = [])
    {
        $pathExplode = explode(DIRECTORY_SEPARATOR, static::class);
        $this->createType = end($pathExplode);
    }

    public function process(): bool
    {
        $request = null;
        $controller = null;
        $module = null;
        $res = '';
        $routes = Router::getRoutes();
        foreach($routes as $route){
            $start = $this->startOutPut($route->getMethods(), $route->getUrl());
            $end = $this->endOutPut($route->getAction(), $route->getName(), $route->getModule());
            $line = $this->lineSpace($start.$end);
            $res .= $this->parseStart($start).$this->getColoredString($line, 'dark_gray').$this->getColoredString(str_replace('>', '›', $end), 'dark_gray')."\n";
        }
        echo $res;
        return true;
    }

    private function startOutPut(array $methods, string $url): string
    {
        $mask = "%-12.12s %-0s ";
        $methods = join('|', $methods);
        return sprintf($mask, $methods, "/$url");
    }

    private function endOutPut(array $controller, string $name = null, string $module = 'root'): string
    {
        $name = $name ? "$name > " : '';
        $module = $module == 'root' ? '' : ucfirst("$module");
        $moduleReplace = $module == 'root' ? '' : $module.'\\';
        $moduleOutPut = $module == 'root' ? '' : $module.':';
        $controller = join('.', $controller);
        $controller = str_replace("App\\{$moduleReplace}Controllers\\", '', $controller);
        return " {$name}{$moduleOutPut}{$controller}";
    }

    private function lineSpace(string $text): string
    {
        return str_repeat("─", intval(shell_exec('tput cols')) - strlen($text));
    }

    private function parseStart(string $outPut): string
    {
        return preg_replace_callback("/(.*?) +\/(.*?) +/", function($e){
            $res1 = '';
            $res2 = '';
            if(isset($e[1])){
                $res1 = $this->parseMethods($e[1]);
            }
            if(isset($e[2])){
                $res2 = $this->parseUrl($e[2]);
            }
            return str_replace([$e[1], $e[2]], [$res1, $res2], $e[0]);
        }, $outPut);
    }

    private function parseMethods(string $methods): string
    {
        foreach (['POST' => 'green', 'GET' => 'blue', 'PUT' => 'dark_gray', 'DELETE' => 'red'] as $method => $color){
            $methods = str_replace($method, $this->getColoredString($method, $color), $methods);
        }
        return $methods;
    }

    private function parseUrl(string $url): string
    {
        return preg_replace('/(\{.*?})/', $this->getColoredString('$1', 'light_yellow', 0), $url);
    }
}