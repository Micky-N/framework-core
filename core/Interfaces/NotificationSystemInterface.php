<?php

namespace MkyCore\Interfaces;

use ErrorException;
use MkyCore\Abstracts\Entity;

interface NotificationSystemInterface
{
    /**
     * Send notification to entity (notifiable)
     *
     * @param Entity $notifiable
     * @param array $message
     * @return mixed
     * @throws ErrorException
     */
    public function send(Entity $notifiable, array $message): mixed;
}