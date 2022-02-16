<?php

namespace MkyCore\Interfaces;


interface ListenerInterface
{

    /**
     * Action on event listening
     *
     * @param EventInterface $event
     * @return mixed
     */
    public function handle(EventInterface $event);
}
