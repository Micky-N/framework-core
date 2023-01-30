<?php

namespace MkyCore\Console;

use Exception;
use MkyCommand\AbstractCommand;
use MkyCommand\Console;
use MkyCommand\Input;
use MkyCore\Application;
use MkyCore\Console\ApplicationCommands\Create\Controller;
use MkyCore\Console\ApplicationCommands\Create\Entity;
use MkyCore\Console\ApplicationCommands\Create\Event;
use MkyCore\Console\ApplicationCommands\Create\Listener;
use MkyCore\Console\ApplicationCommands\Create\Manager;
use MkyCore\Console\ApplicationCommands\Create\Middleware;
use MkyCore\Console\ApplicationCommands\Create\Module;
use MkyCore\Console\ApplicationCommands\Create\Provider;
use MkyCore\Console\ApplicationCommands\Generate\Key;
use MkyCore\Console\ApplicationCommands\Install\Jwt;
use MkyCore\Console\ApplicationCommands\Install\Remember;
use MkyCore\Console\ApplicationCommands\Migration\Create;
use MkyCore\Console\ApplicationCommands\Migration\Refresh;
use MkyCore\Console\ApplicationCommands\Migration\Reset;
use MkyCore\Console\ApplicationCommands\Migration\Rollback;
use MkyCore\Console\ApplicationCommands\Migration\Run;
use MkyCore\Console\ApplicationCommands\Populator\Create as PopulatorCreate;
use MkyCore\Console\ApplicationCommands\Populator\Run as PopulatorRun;
use MkyCore\Console\ApplicationCommands\Schedule\Cron as ScheduleCron;
use MkyCore\Console\ApplicationCommands\Schedule\Run as ScheduleRun;
use MkyCore\Console\ApplicationCommands\Show\Module as ShowModule;
use MkyCore\Console\ApplicationCommands\Show\Route;
use MkyCore\Console\ApplicationCommands\Tmp\Link;
use MkyCore\Exceptions\Container\FailedToResolveContainerException;
use MkyCore\Exceptions\Container\NotInstantiableContainerException;
use ReflectionException;

class NodeConsoleHandler extends Console
{

    public array $customCommands = [];
    public mixed $response = '';

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    public function __construct(private readonly Application $application)
    {
        if (! defined('MKY_FILE')) {
            define('MKY_FILE', 'mky');
        }

        parent::__construct();
        $this->setInitCommands();
        $this->setCustomCommands();
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws FailedToResolveContainerException
     * @throws ReflectionException
     */
    private function setInitCommands(): void
    {
        $this->addCommand('create:controller', $this->application->get(Controller::class))
            ->addCommand('create:entity', $this->application->get(Entity::class))
            ->addCommand('create:event', $this->application->get(Event::class))
            ->addCommand('create:listener', $this->application->get(Listener::class))
            ->addCommand('create:manager', $this->application->get(Manager::class))
            ->addCommand('create:middleware', $this->application->get(Middleware::class))
            ->addCommand('create:module', $this->application->get(Module::class))
            ->addCommand('create:provider', $this->application->get(Provider::class))
            ->addCommand('generate:key', $this->application->get(Key::class))
            ->addCommand('install:jwt', $this->application->get(Jwt::class))
            ->addCommand('install:remember', $this->application->get(Remember::class))
            ->addCommand('migration:create', $this->application->get(Create::class))
            ->addCommand('migration:refresh', $this->application->get(Refresh::class))
            ->addCommand('migration:reset', $this->application->get(Reset::class))
            ->addCommand('migration:rollback', $this->application->get(Rollback::class))
            ->addCommand('migration:run', $this->application->get(Run::class))
            ->addCommand('populator:create', $this->application->get(PopulatorCreate::class))
            ->addCommand('populator:run', $this->application->get(PopulatorRun::class))
            ->addCommand('show:module', $this->application->get(ShowModule::class))
            ->addCommand('show:routes', $this->application->get(Route::class))
            ->addCommand('tmp:link', $this->application->get(Link::class))
            ->addCommand('schedule:run', $this->application->get(ScheduleRun::class))
            ->addCommand('schedule:cron', $this->application->get(ScheduleCron::class));
    }

    /**
     * @throws NotInstantiableContainerException
     * @throws ReflectionException
     * @throws FailedToResolveContainerException
     */
    private function setCustomCommands(): void
    {
        foreach ($this->application->getCommands() as $signature => $command) {
            $command = $this->application->get($command);
            /** @var AbstractCommand $command */
            $this->addCommand($signature, $command);
            $this->customCommands[$signature] = $command;
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

    /**
     * @return void
     */
    public function send(): void
    {
        exit($this->response);
    }
}