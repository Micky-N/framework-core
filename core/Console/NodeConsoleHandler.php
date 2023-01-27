<?php

namespace MkyCore\Console;

use Exception;
use MkyCommand\Input;
use MkyCommand\Console;
use MkyCore\Application;
use MkyCore\Console\Tmp\Link;
use MkyCore\Console\Show\Route;
use MkyCore\Console\Install\Jwt;
use MkyCore\Console\Create\Event;
use MkyCore\Console\Generate\Key;
use MkyCore\Console\Create\Entity;
use MkyCore\Console\Create\Module;
use MkyCore\Console\Migration\Run;
use MkyCore\Console\Create\Manager;
use MkyCore\Console\Create\Listener;
use MkyCore\Console\Create\Provider;
use MkyCore\Console\Migration\Reset;
use MkyCore\Console\Install\Remember;
use MkyCore\Console\Migration\Create;
use MkyCore\Console\Create\Controller;
use MkyCore\Console\Create\Middleware;
use MkyCore\Console\Migration\Refresh;
use MkyCore\Console\Migration\Rollback;
use MkyCore\Console\Show\Module as ShowModule;
use MkyCore\Console\Populator\Run as PopulatorRun;
use MkyCore\Console\Populator\Create as PopulatorCreate;

class NodeConsoleHandler extends Console
{

    public array $customCommands = [];
    public mixed $response = '';

    public function __construct(private readonly Application $app)
    {
        parent::__construct($app);
        $this->setInitCommands();
        $this->setCustomCommands();
    }

    private function setInitCommands(): void
    {
        $this->addCommand('create:controller', Controller::class)
            ->addCommand('create:entity', Entity::class)
            ->addCommand('create:event', Event::class)
            ->addCommand('create:listener', Listener::class)
            ->addCommand('create:manager', Manager::class)
            ->addCommand('create:middleware', Middleware::class)
            ->addCommand('create:module', Module::class)
            ->addCommand('create:provider', Provider::class)
            ->addCommand('generate:key', Key::class)
            ->addCommand('install:jwt', Jwt::class)
            ->addCommand('install:remember', Remember::class)
            ->addCommand('migration:create', Create::class)
            ->addCommand('migration:refresh', Refresh::class)
            ->addCommand('migration:reset', Reset::class)
            ->addCommand('migration:rollback', Rollback::class)
            ->addCommand('migration:run', Run::class)
            ->addCommand('populator:create', PopulatorCreate::class)
            ->addCommand('populator:run', PopulatorRun::class)
            ->addCommand('show:module', ShowModule::class)
            ->addCommand('show:routes', Route::class)
            ->addCommand('tmp:link', Link::class);
    }

    private function setCustomCommands(): void
    {
        if($this->app->getCommands()){
            foreach ($this->app->getCommands() as $signature => $command) {
                $this->addCommand($signature, $command);
                $this->customCommands[$signature] = is_string($command) ? $command : get_class($command);
            }
        }
    }

    /**
     * @return string[]
     */
    public function getCustomCommands(): array
    {
        return $this->customCommands;
    }

    public function handle(Input $input): static
    {
        try {
            $this->response = $this->execute($input);
            return $this;
        }catch(Exception $ex){
            $command = $this->output->coloredMessage('php mky help', 'yellow');
            exit($ex->getMessage()."\nrun $command to see the list of commands");
        }
    }

    public function send(): void
    {
        exit($this->response);
    }
}