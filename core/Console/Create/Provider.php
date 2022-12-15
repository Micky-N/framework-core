<?php

namespace MkyCore\Console\Create;

class Provider extends Create
{
    protected string $outputDirectory = 'Providers';
    protected array $rules = [
        'name' => ['ucfirst', 'ends:ServiceProvider'],
    ];

    public function handleQuestions(array $replaceParams, array $params = []): array
    {
        $replaceParams['doc'] = '';
        $name = $replaceParams['name'];

        if(str_starts_with($name, 'Auth')){
            $replaceParams['doc'] = $this->setAuthDocs();
        }elseif(str_starts_with($name, 'App')){
            $replaceParams['doc'] = $this->setAppDocs();
        }elseif(str_starts_with($name, 'Event')){
            $replaceParams['doc'] = $this->setEventDocs();
        }
        return $replaceParams;
    }

    private function setAuthDocs(): string
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

    private function setAppDocs(): string
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

    private function setEventDocs(): string
    {
        return <<<DOCS

    /**
     * Register events and their listeners
     * @example app->addEvent(Event::class, ['action' => Listener::class]);
     * and register notification systems
     * @example app->addNotificationSystem('example', ExampleNotificationSystem::class);
     *
     * @return void
     */
DOCS;

    }
}