<?php

namespace MkyCore\Interfaces;

use ErrorException;

interface NotificationSystemInterface
{
    /**
     * Send notification to entity (notifiable)
     *
     * @param $notifiable
     * @param array $message
     * @throws ErrorException
     */
    public function send($notifiable, array $message): void;
}