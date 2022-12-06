<?php

namespace Console\Create;

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
        }elseif(str_starts_with($name, 'Application')){
            $replaceParams['doc'] = $this->setAppDocs();
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
     * @exemple Route('/', cans: [alias])
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
}