<?php

namespace MkyCore\Console\Show;

use MkyCore\Console\Color;
use MkyCore\Facades\Router;

class Route
{

    use Color;

    protected string|false $createType;
    private int $cols;
    private int $nwords = 0;

    public function __construct(protected readonly array $params = [], protected array $moduleOptions = [])
    {
        $pathExplode = explode(DIRECTORY_SEPARATOR, static::class);
        $this->createType = end($pathExplode);
        $this->cols = intval(shell_exec('tput cols'));
    }

    public function process(): bool
    {
        $request = null;
        $controller = null;
        $module = null;
        $res = '';
        $routes = Router::getRoutes();
        foreach ($routes as $route) {
            $this->nwords = 0;
            $start = $this->startOutPut($route->getMethods(), $route->getUrl());
            $end = $this->endOutPut($route->getAction(), $route->getName(), $route->getModule());
            $end = str_replace('>', '›', $end);
            $line = $this->lineSpace($this->parseStart($start), $end);
            $res .= $line .  PHP_EOL;
        }
        echo $res;
        return true;
    }

    private function startOutPut(array $methods, string $url): string
    {
        $url = trim($url, '/');
        $mask = "%-12s %-0s ";
        $methods = join('|', $methods);
        $output = sprintf($mask, $methods, "/$url");
        return $output;
    }

    private function endOutPut(array $controller, string $name = null, string $module = 'root'): string
    {
        $name = $name ? "$name > " : '';
        $module = $module == 'Root' ? '' : ucfirst("$module");
        $moduleReplace = !$module ? '' : $module . '\\';
        $moduleOutPut = !$module ? '' : $module . ':';
        $controller = join('.', $controller);
        $controller = str_replace("App\\{$moduleReplace}Controllers\\", '', $controller);
        $output = " {$name}{$moduleOutPut}{$controller}";
        return $output;
    }

    private function lineSpace(string $start, string $end): string
    {
        $line = str_pad($start, $this->cols + $this->nwords - 5 - mb_strlen($end), '-');
        return str_replace('-', '─', $line).$end;
    }

    private function parseStart(string $outPut): string
    {
        return preg_replace_callback("/(.*?) +\/(.*?) +/", function ($e) {
            $res1 = '';
            $res2 = '';
            if (isset($e[1])) {
                $res1 = $this->parseMethods($e[1]);
            }
            if (isset($e[2])) {
                $res2 = $this->parseUrl($e[2]);
            }
            $res = str_replace([$res1, $res2], [$res1, $res2], $e[0]);
            return $res;
        }, $outPut);
    }

    private function parseMethods(string $methods): string
    {
        foreach (['POST' => 'green', 'GET' => 'blue', 'PUT' => 'dark_gray', 'DELETE' => 'red'] as $method => $color) {
            $methods = str_replace($method, $this->getColoredString($method, $color), $methods);
        }
        return $methods;
    }

    private function parseUrl(string $url): string
    {
        return preg_replace('/(\{.*?})/', $this->getColoredString('$1', 'light_yellow', 0), $url);
    }
}