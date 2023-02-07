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
    protected string $viewHtml = 'template';
    protected string $viewText = 'template_text';
    protected array $texts = [];
    protected array $blocks = [];

    /**
     * @inheritDoc
     * @return string
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
     */
    protected function outPutTemplate(string $view, array $params = []): string
    {
        $namespace = self::NAMESPACE;
        $loader = new DirectoryLoader($this->viewPath ?: dirname(__DIR__) . '/views');
        $mkyRender = View::addPath($namespace, $loader);
        return $mkyRender->toHtml("@$namespace:" . $view, $params);
    }

    /**
     * @return string|false
     * @throws EnvironmentException
     */
    public function generateText(): string|false
    {
        return $this->outPutTemplate($this->viewText, $this->texts);
    }

    /**
     * @param array|string $viewTemplates
     * @return $this
     */
    public function use(array|string $viewTemplates): static
    {
        if (is_array($viewTemplates)) {
            $this->viewHtml = $viewTemplates['html'] ?? '';
            $this->viewText = $viewTemplates['text'] ?? '';
        } else if (is_string($viewTemplates)) {
            $this->viewHtml = $viewTemplates;
        }
        return $this;
    }
}