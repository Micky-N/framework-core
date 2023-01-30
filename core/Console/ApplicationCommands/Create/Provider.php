<?php

namespace MkyCore\Console\ApplicationCommands\Create;

use MkyCommand\Input;
use MkyCommand\Output;
use MkyCore\Abstracts\ModuleKernel;

class Provider extends Create
{
    protected string $outputDirectory = 'Providers';
    protected string $createType = 'provider';
    protected string $suffix = 'ServiceProvider';

    protected string $description = 'Create a new provider';

    public function settings(): void
    {
        $this->addArgument('name', Input\InputArgument::REQUIRED, 'Name of the provider, by default is suffixed by ServiceProvider');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @param ModuleKernel $moduleKernel
     * @param array $vars
     * @return void
     */
    public function gettingStarted(Input $input, Output $output, ModuleKernel $moduleKernel, array &$vars): void
    {
        $doc = '';
        $name = $vars['name'];
        if (str_starts_with($name, 'AuthService')) {
            $doc = $this->setAuthServiceDocs();
        } elseif (str_starts_with($name, 'AppService')) {
            $doc = $this->setAppServiceDocs();
        } elseif (str_starts_with($name, 'EventService')) {
            $doc = $this->setEventServiceDocs();
        }
        $vars['doc'] = $doc;
    }

    private function setAuthServiceDocs(): string
    {
        return <<<DOCS

    /**
     * Register all permissions with Allows facade
     * @example \MkyCore\Facades\Allows::define(alias, callback(user, entity))
     * To use the permission, alias must be implements in a route definition like
     * @exemple Route('/', allows: [alias])
     * 
     * @return void
     */
DOCS;

    }

    private function setAppServiceDocs(): string
    {
        return <<<DOCS

    /**
     * Register classes in the container
     * @example app->bind(alias, callback)
     * or use app->singleton(alias, callback) to share the same instance throughout the application
     * 
     * @return void
     */
DOCS;

    }

    private function setEventServiceDocs(): string
    {
        return <<<DOCS

    /**
     * Register events and their listeners
     * @example app->addEvent(Event::class, ['action' => Listener::class]);
     *
     * @return void
     */
DOCS;

    }
}