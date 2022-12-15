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
        $extensions = ['TwigExtensionFunction', 'TwigExtensionFilter'];
        $this->twig->addExtension(new TwigExtensionFunction());
        $this->twig->addExtension(new TwigExtensionFilter());
        for ($i = 0; $i < count($extensions); $i++) {
            $extension = $extensions[$i];
            if (class_exists($extensionClass = 'App\TwigExtensions\\' . $extension)) {
                $extensionClass = new $extensionClass();
                if (!($extensionClass instanceof AbstractExtension)) {
                    continue;
                }
                if ($extension == 'TwigFunction' && !method_exists($extensionClass, 'getFunctions')) {
                    continue;
                } elseif ($extension == 'TwigFilter' && !method_exists($extensionClass, 'getFilters')) {
                    continue;
                }

                $this->twig->addExtension(new $extensionClass());
            }
        }
        $this->twig->getExtension(\Twig\Extension\CoreExtension::class)->setTimezone(Config::get('app.timezone'));

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