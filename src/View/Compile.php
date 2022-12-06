<?php

namespace MkyCore\View;

use Exception;
use MkyCore\Interfaces\ViewCompileInterface;

class Compile implements ViewCompileInterface
{
    const PARSE_ITEMS = [
        'echo' => ['{{', '}}'],
        'functions' => ['< *!-- *\[', '\] *-->'],
    ];
    const VIEW_EXTENSION = '.html.php';
    private array $params = [];
    private string $view;
    private string $viewPath;
    private string $cachePath;

    /**
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->viewPath = $config['views'];
        $this->cachePath = $config['cache_views'];
    }

    /**
     * @throws Exception
     */
    public function compile(string $view, array $params = []): string
    {
        if (!file_exists(rtrim($this->viewPath, '/\\') . DIRECTORY_SEPARATOR . $view . self::VIEW_EXTENSION)) {
            throw new Exception("The view file {$view} does not exists!");
        }
        $this->view = $view;
        $this->params = array_merge($this->params, $params);
        return $this->parseView();
    }

    private function parseView(): bool|string
    {
        $view = file_get_contents(rtrim($this->viewPath, '/\\') . DIRECTORY_SEPARATOR . $this->view . self::VIEW_EXTENSION);
        $this->parseExtends($view);
        $this->parseIncludes($view);
        $this->parseVariables($view);
        ob_start();
        extract($this->params);
        $flash = fn($type) => \MkyCore\Facades\Session::pull($type, []);
        eval("?>$view<?php");
        return ob_get_clean() ?: '';
    }

    private function parseExtends(string &$view): void
    {
        $brackets = self::PARSE_ITEMS['functions'];
        preg_match('/' . $brackets[0] . 'extends:(.*?)' . $brackets[1] . '/', $view, $matches);
        if (!$matches) {
            return;
        }
        $extends = $matches[1];
        $extendsView = $this->viewPath . str_replace('.', DIRECTORY_SEPARATOR, $extends) . self::VIEW_EXTENSION;
        if (!file_exists($extendsView)) {
            return;
        }
        $extendsView = file_get_contents($extendsView);
        $extendsView = preg_replace_callback('/' . $brackets[0] . 'section:(.*?)' . $brackets[1] . '/', function ($expression) use ($view, $brackets) {
            if (isset($expression[1])) {
                preg_match('/' . $brackets[0] . 'section:' . $expression[1] . $brackets[1] . '(.*?)' . $brackets[0] . 'endsection' . $brackets[1] . '/s', $view, $matches);
                return isset($matches[1]) ? trim($matches[1]) : '';
            }
            return '';
        }, $extendsView);
        $view = $extendsView;
    }

    private function parseIncludes(string &$view): void
    {
        $brackets = self::PARSE_ITEMS['functions'];
        $includedView = preg_replace_callback('/' . $brackets[0] . 'includes:(.*?)\](\[(.*?)\])*' . $brackets[1] . '/', function ($expression) {
            if (count($expression) >= 2) {
                $includeFile = $this->viewPath . "$expression[1].php";
                if (!file_exists($includeFile)) {
                    return $expression[0];
                }
                $includeFile = file_get_contents($includeFile);
                if (isset($expression[3])) {
                    $explodedParams = explode(',', $expression[3]);
                    $params = [];
                    foreach ($explodedParams as $explodedParam) {
                        $explodedParam = trim($explodedParam);
                        $paramExplode = explode(':', $explodedParam);
                        $value = $paramExplode[1];
                        if (str_starts_with($value, '$')) {
                            $key = str_replace('$', '', $value);
                            $value = $this->params[$key];
                        } elseif (str_starts_with($paramExplode[1], '{') && str_ends_with($paramExplode[1], '}')) {
                            $value = trim($value, '{}');
                            extract($this->params);
                            eval("\$value = $value;");
                        }
                    }
                    $params[$paramExplode[0]] = $value;
                    $this->params = array_merge($this->params, $params);
                }
                return $includeFile;
            }
            return $expression[0];
        }, $view);
        $view = $includedView;
    }

    private function parseVariables(string &$view): void
    {
        $brackets = self::PARSE_ITEMS['echo'];
        $view = preg_replace_callback('/' . $brackets[0] . '(.*?)' . $brackets[1] . '/', function ($expression) {
            if (count($expression) >= 2) {
                $variable = trim($expression[1]);
                return "<?= $variable ?>";
            }
            return $expression[0];
        }, $view);
    }
}