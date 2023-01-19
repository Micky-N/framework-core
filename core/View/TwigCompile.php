<?php

namespace MkyCore\View;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Facades\Config;
use MkyCore\Interfaces\ViewCompileInterface;
use MkyCore\TwigExtensions\TwigExtensionFilter;
use MkyCore\TwigExtensions\TwigExtensionFunction;
use ReflectionException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Loader\FilesystemLoader;

class TwigCompile implements ViewCompileInterface
{

    private Environment $twig;
    private FilesystemLoader $loader;

    public function __construct(array $options)
    {
        $baseViews = str_replace(DIRECTORY_SEPARATOR . 'public', '', getcwd()) . DIRECTORY_SEPARATOR . 'views';
        $this->loader = new FilesystemLoader($baseViews);
        $this->twig = new Environment($this->loader, $options);
    }

    /**
     * @param string $view
     * @param array $params
     * @return string
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function compile(string $view, array $params = []): string
    {
        $this->twig->addGlobal('request', app()->get(TwigRequest::class));
        if(!$this->twig->hasExtension(TwigExtensionFunction::class)){
            $this->twig->addExtension(new TwigExtensionFunction());
        }
        if(!$this->twig->hasExtension(TwigExtensionFilter::class)){
            $this->twig->addExtension(new TwigExtensionFilter());
        }
        $rootKernel = app()->getModuleKernel('root');
        $methods = ['getTwigFunctions', 'getTwigFilters'];
        for ($i = 0; $i < count($methods); $i++) {
            $method = $methods[$i];
            if (!method_exists($rootKernel, $method)) {
                continue;
            }
            $extensions = $rootKernel->$method();
            if (!$extensions) {
                continue;
            }
            for ($j = 0; $j < count($extensions); $j++) {
                $extension = $extensions[$j];
                if (!($extension instanceof AbstractExtension)) {
                    throw new RuntimeError(sprintf('Class %s must extends %s class', get_class($extension), AbstractExtension::class));
                }
                if(!$this->twig->hasExtension(get_class($extension))){
                    $this->twig->addExtension($extension);
                }
            }
        }
        if(method_exists($rootKernel, 'getTwigGlobalVariables')){
            $globalVariables = $rootKernel->getTwigGlobalVariables();
            foreach ($globalVariables as $name => $value){
                $this->twig->addGlobal($name, $value);
            }
        }
        $this->twig->getExtension(CoreExtension::class)->setTimezone(Config::get('app.timezone'));

        return $this->twig->render($view, $params);
    }

    /**
     * @throws LoaderError
     */
    public function addPath(string $path, string $namespace)
    {
        $this->loader->addPath($path, $namespace);
    }
}