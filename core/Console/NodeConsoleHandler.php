<?php

namespace MkyCore\Console;

use Exception;
use MkyCommand\HelpCommand;
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
        $this->addCommand('help', new HelpCommand($this))
            ->addCommand('create:controller', $this->app->get(Controller::class))
            ->addCommand('create:entity', $this->app->get(Entity::class))
            ->addCommand('create:event', $this->app->get(Event::class))
            ->addCommand('create:listener', $this->app->get(Listener::class))
            ->addCommand('create:manager', $this->app->get(Manager::class))
            ->addCommand('create:middleware', $this->app->get(Middleware::class))
            ->addCommand('create:module', $this->app->get(Module::class))
            ->addCommand('create:provider', $this->app->get(Provider::class))
            ->addCommand('generate:key', $this->app->get(Key::class))
            ->addCommand('install:jwt', $this->app->get(Jwt::class))
            ->addCommand('install:remember', $this->app->get(Remember::class))
            ->addCommand('migration:create', $this->app->get(Create::class))
            ->addCommand('migration:refresh', $this->app->get(Refresh::class))
            ->addCommand('migration:reset', $this->app->get(Reset::class))
            ->addCommand('migration:rollback', $this->app->get(Rollback::class))
            ->addCommand('migration:run', $this->app->get(Run::class))
            ->addCommand('populator:create', $this->app->get(PopulatorCreate::class))
            ->addCommand('populator:run', $this->app->get(PopulatorRun::class))
            ->addCommand('show:module', $this->app->get(ShowModule::class))
            ->addCommand('show:routes', $this->app->get(Route::class))
            ->addCommand('tmp:link', $this->app->get(Link::class));
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