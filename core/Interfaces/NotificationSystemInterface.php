<?php

namespace MkyCore\Interfaces;

use ErrorException;

interface NotificationSystemInterface
{
    /**
     * @param $notifiable
     * @param array $message
     * @throws ErrorException
     */
    public function send($notifiable, array $message);
}