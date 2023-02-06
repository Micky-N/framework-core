<?php

namespace MkyCore\Abstracts;

use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use MkyCore\Exceptions\ViewSystemException;
use MkyCore\Facades\View;
use MkyCore\Interfaces\MailerTemplateInterface;
use MkyEngine\DirectoryLoader;
use MkyEngine\Exceptions\EnvironmentException;
use ReflectionException;

class AbstractMailerTemplate implements MailerTemplateInterface
{
    const NAMESPACE = 'mailer';
    protected string $viewPath = '';
    protected string $viewHtml = 'template.php';
    protected string $viewText = 'template_text.php';
    protected array $texts = [];
    protected array $blocks = [];

    /**
     * @inheritDoc
     * @return string
     * @throws FailedToResolveContainerException
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws ViewSystemException
     * @throws EnvironmentException
     */
    public function generate(): string
    {
        return $this->outPutTemplate($this->viewHtml, $this->blocks);
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
    protected function outPutTemplate(string $view, array $params = []): string
    {
        $namespace = self::NAMESPACE;
        $loader = new DirectoryLoader($this->viewPath ?: dirname(__DIR__) . '/views');
        $mkyRender = View::addPath($namespace, $loader);
        return $mkyRender->toHtml("@$namespace:" . $view, $params);
    }

    /**
     * @throws FailedToResolveContainerException
     * @throws EnvironmentException
     * @throws ReflectionException
     * @throws NotInstantiableContainerException
     * @throws ViewSystemException
     */
    public function generateText(): string|false
    {
        return $this->outPutTemplate($this->viewText, $this->texts);
    }
}