<?php

namespace MkyCore;

use Exception;
use MkyCore\Abstracts\ModuleKernel;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\ViewSystemException;
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
    private DirectoryLoader $loader;
    private Environment $environment;

    /**
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     */
    public function __construct(private readonly Application $app)
    {
        $module = $this->app->getModuleKernel($app->getCurrentRoute()->getModule());
        $viewDirectory = $module->getModulePath() . '/views';
        $this->loader = new DirectoryLoader($viewDirectory);
        $this->loader->setComponentDir('components')
            ->setLayoutDir('layouts');
        $context = $module->sharedVariables();
        $context['request'] = app()->get(MkyEngineRequest::class);
        $this->environment = new Environment($this->loader, context: $context);
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
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws ViewSystemException
     */
    public function toHtml(string $view, array $params = []): string
    {
        $module = $this->app->getModuleKernel($this->app->getCurrentRoute()->getModule());

        $viewsModuleConfig = $module->getConfig('views_mode', 'base');
        if (!in_array($viewsModuleConfig, self::VIEWS_STRATEGIES)) {
            throw new ViewSystemException("View config $viewsModuleConfig not correct, must be base, module or parent");
        }
        if ($viewsModuleConfig == 'parent') {
            $this->getParentViewsDirectory($module);
        } elseif ($viewsModuleConfig == 'module') {
            $this->getModuleViewsDirectory($module);
        }
        if (str_starts_with($view, '@:')) {
            $view = str_replace('@', '@' . $module->getAlias(), $view);
        }
        $viewCompile = new ViewCompiler($this->environment, $view, $params);
        return $this->renderedView = $viewCompile->render();
    }

    /**
     * @param string $namespace
     * @param DirectoryLoader $loader
     * @return $this
     * @throws EnvironmentException
     */
    public function addPath(string $namespace, DirectoryLoader $loader): static
    {
        if(!$this->environment->hasLoader($namespace)){
            $this->environment->addLoader($namespace, $loader);
        }
        return $this;
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
        /** @var ModuleKernel[] $ancestors */
        $ancestors = $moduleKernel->getAncestorsKernel();
        foreach ($ancestors as $ancestor) {
            $loader = new DirectoryLoader($ancestor->getModulePath() . DIRECTORY_SEPARATOR . 'views');
            $this->addPath($ancestor->getAlias(), $loader);
        }
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

            $this->addPath($module->getAlias(), new DirectoryLoader($viewPath));
        }
    }

    public function handle(): Response
    {
        return new Response(200, ['content-type' => 'text/html'], $this->renderedView);
    }
}
