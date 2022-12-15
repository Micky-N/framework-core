<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\ViewSystemException;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\Interfaces\ViewCompileInterface;
use MkyCore\View\TwigCompile;
use ReflectionException;
use Twig\Error\LoaderError;

class View implements ResponseHandlerInterface
{
    const VIEWS_STRATEGIES = ['base', 'module', 'parent', 'both'];
    private ?string $renderedView = null;
    private ViewCompileInterface $compile;

    public function __construct(private readonly Application $app)
    {
        $compile = new TwigCompile(\MkyCore\Facades\Config::get('twig_options', []));
        $this->compile = $compile;
    }

    /**
     * @throws Exception
     */
    public function render(string $view, array $params = []): View
    {
        $module = $this->app->getCurrentRoute()->getModule();
        if ($module) {
            $module = $this->app->getModuleKernel($module);
            $viewsModuleDirectory = $module->getModulePath() . DIRECTORY_SEPARATOR . 'views';
            if (file_exists($viewsModuleDirectory)) {
                $this->compile->addPath($viewsModuleDirectory, $module->getAlias());
            }
            $viewsModuleConfig = $module->getConfig('views_mode', 'base');
            if (!in_array($viewsModuleConfig, self::VIEWS_STRATEGIES)) {
                throw new ViewSystemException("View config $viewsModuleConfig not correct, must be base, module or parent");
            }
            switch ($viewsModuleConfig) {
                case 'both':
                    $this->getModuleViewsDirectory($module);
                case 'parent':
                    $this->getParentViewsDirectory($module);
                    break;
                case 'module':
                    $this->getModuleViewsDirectory($module);
                    break;
            }
        }
        if (str_starts_with($view, '@/')) {
            $view = str_replace('@', '@' . $module->getAlias(), $view);
        }
        $this->renderedView = $this->compile->compile($view, $params);
        return $this;
    }

    /**
     * @throws LoaderError
     */
    public function addPath(string $path, string $namespace): void
    {
        $this->compile->addPath($path, $namespace);
    }

    private function getParentViewsDirectory(ModuleKernel $moduleKernel): void
    {
        /** @var ModuleKernel[] $ancestors */
        $ancestors = $moduleKernel->getAncestorsKernel();
        foreach ($ancestors as $ancestor) {
            $this->compile->addPath($ancestor->getModulePath() . DIRECTORY_SEPARATOR . 'views', $ancestor->getAlias());
        }
    }

    /**
     * @param ModuleKernel $moduleKernel
     * @return void
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws LoaderError
     * @throws ReflectionException
     * @throws Exception
     */
    private function getModuleViewsDirectory(ModuleKernel $moduleKernel): void
    {
        $viewsModules = (array)$moduleKernel->getConfig('modules', []);
        if (!$viewsModules) {
            return;
        }
        for ($i = 0; $i < count($viewsModules); $i++) {
            $module = $viewsModules[$i];
            if (!$this->app->hasModule($module)) {
                throw new Exception("Alias module $module not found");
            }
            $module = $this->app->getModuleKernel($module);
            $viewPath = $module->getModulePath() . DIRECTORY_SEPARATOR . 'views';
            if (!file_exists($viewPath)) {
                continue;
            }

            $this->compile->addPath($viewPath, $module->getAlias());
        }
    }

    public function handle(): Response
    {
        return new Response(200, ['content-type' => 'text/html'], $this->renderedView);
    }
}
