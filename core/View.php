<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\ViewSystemException;
use MkyCore\Facades\Config;
use MkyCore\Interfaces\ResponseHandlerInterface;
use MkyCore\View\MkyEngineRequest;
use MkyEngine\DirectoryLoader;
use MkyEngine\Environment;
use MkyEngine\Exceptions\EnvironmentException;
use MkyEngine\ViewCompiler;
use ReflectionException;

class View implements ResponseHandlerInterface
{
    const VIEWS_STRATEGIES = ['base', 'module', 'parent', 'both'];
    private ?string $renderedView = null;
    private Environment $environment;
    private ModuleKernel $module;

    /**
     * @param Application $app
     * @throws EnvironmentException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws ViewSystemException
     */
    public function __construct(private readonly Application $app)
    {
        $this->module = $this->app->getModuleKernel($app->getCurrentRoute()->getModule());
        $this->rootViewDirectory();
        if ($this->module->getAlias() !== 'root') {
            $this->currentViewDirectory();
        }
        $this->viewModuleConfig();
    }

    /**
     * @return void
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function rootViewDirectory(): void
    {
        $context = $this->module->sharedVariables();
        $context['request'] = $this->app->get(MkyEngineRequest::class);
        $rootView = $this->setDirectoryLoader(app()->getBasePath() . '/views', true);
        $this->environment = new Environment($rootView, $context);
    }

    private function setDirectoryLoader(string $path, bool $root = false): DirectoryLoader
    {
        $componentDir = $root ? Config::getBase('mkyengine.componentDir', 'components') : Config::get('mkyengine.componentDir', 'components');
        $layoutDir = $root ? Config::getBase('mkyengine.layoutDir', 'layouts') : Config::get('mkyengine.layoutDir', 'layouts');
        return (new DirectoryLoader($path))->setComponentDir($componentDir)
            ->setLayoutDir($layoutDir);
    }

    /**
     * @return void
     * @throws EnvironmentException
     */
    private function currentViewDirectory(): void
    {
        $viewDirectory = $this->module->getModulePath() . '/views';
        $currentLoader = $this->setDirectoryLoader($viewDirectory);
        $this->environment->addLoader($this->module->getAlias(), $currentLoader);
    }

    /**
     * @return void
     * @throws EnvironmentException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws ViewSystemException
     */
    private function viewModuleConfig(): void
    {
        $viewsModuleConfig = $this->module->getConfig('views_mode', 'base');
        if (!in_array($viewsModuleConfig, self::VIEWS_STRATEGIES)) {
            throw new ViewSystemException("View config $viewsModuleConfig not correct, must be base, module or parent");
        }
        if ($viewsModuleConfig == 'parent') {
            $this->getParentViewsDirectory($this->module);
        } elseif ($viewsModuleConfig == 'module') {
            $this->getModuleViewsDirectory($this->module);
        }
    }

    /**
     * @param ModuleKernel $moduleKernel
     * @throws EnvironmentException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     */
    private function getParentViewsDirectory(ModuleKernel $moduleKernel): void
    {
        $ancestors = $moduleKernel->getAncestorsKernel();
        foreach ($ancestors as $ancestor) {
            $loader = $this->setDirectoryLoader($ancestor->getModulePath() . DIRECTORY_SEPARATOR . 'views');
            $this->addPath($ancestor->getAlias(), $loader);
        }
    }

    /**
     * @param string $namespace
     * @param DirectoryLoader $loader
     * @return $this
     * @throws EnvironmentException
     */
    public function addPath(string $namespace, DirectoryLoader $loader): static
    {
        if (!$this->environment->hasLoader($namespace)) {
            $this->environment->addLoader($namespace, $loader);
        }
        return $this;
    }

    /**
     * @param ModuleKernel $moduleKernel
     * @return void
     * @throws EnvironmentException
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
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

            $this->addPath($module->getAlias(), $this->setDirectoryLoader($viewPath));
        }
    }

    /**
     * @throws Exception
     */
    public function render(string $view, array $params = []): View
    {
        $this->toHtml($view, $params);
        return $this;
    }

    /**
     * @param string $view
     * @param array $params
     * @return string
     * @throws EnvironmentException
     */
    public function toHtml(string $view, array $params = []): string
    {
        if (str_starts_with($view, '@:')) {
            $view = str_replace('@', '@' . $this->module->getAlias(), $view);
        }
        $viewCompile = new ViewCompiler($this->environment, $view, $params);
        return $this->renderedView = $viewCompile->render();
    }

    public function handle(): Response
    {
        return new Response(200, ['content-type' => 'text/html'], $this->renderedView);
    }
}
