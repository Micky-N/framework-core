<?php


namespace MkyCore\Traits;


use ReflectionClass;
use ReflectionException;
use MkyCore\Application;
use MkyCore\Exceptions\Dispatcher\EventNotFoundException;
use MkyCore\Exceptions\Dispatcher\EventNotImplementException;
use MkyCore\Exceptions\Dispatcher\ListenerNotFoundException;
use MkyCore\Exceptions\Dispatcher\ListenerNotImplementException;
use MkyCore\Interfaces\EventInterface;
use MkyCore\Interfaces\ListenerInterface;

trait Dispatcher
{

    /**
     * Run events and listeners
     *
     * @param null $target
     * @param array|string|null $actions
     * @param array $params
     * @return EventInterface
     * @throws EventNotFoundException
     * @throws EventNotImplementException
     * @throws ListenerNotFoundException
     * @throws ListenerNotImplementException
     * @throws ReflectionException
     */
    public static function dispatch($target = null, array|string $actions = null, array $params = []): EventInterface
    {
        $class = new ReflectionClass(get_called_class());
        $actions = (array)$actions;
        $event = $class->newInstance($target, $actions, $params);
        if (!($event instanceof EventInterface)) {
            throw new EventNotImplementException(sprintf("%s must implement %s interface", $class->getName(), EventInterface::class));
        }
        if (!in_array(null, $event->getActions())) {
            if (!is_null(app()->getListeners($class->getName()))) {
                foreach ($event->getActions() as $action) {
                    if ($event->isPropagationStopped()) {
                        break;
                    }
                    $actionName = $action;
                    $action = app()->getListenerActions($class->getName(), $action);
                    if (is_null($action)) {
                        throw new ListenerNotFoundException(sprintf("%s doesn't have a listener for '%s' action in the EventServiceProvider", $class->getName(), $actionName));
                    }
                    $action = (new $action());
                    if (!($action instanceof ListenerInterface)) {
                        throw new ListenerNotImplementException(sprintf("%s must implement %s interface", $actionName, ListenerInterface::class));
                    }
                    $action->handle($event);
                }
            } else {
                throw new EventNotFoundException(sprintf("%s is not defined in the EventServiceProvider", $class->getName()));
            }
        }
        return $event;
    }
}