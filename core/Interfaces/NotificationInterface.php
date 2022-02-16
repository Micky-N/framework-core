<?php


namespace MkyCore\Interfaces;


interface NotificationInterface
{
    /**
     * Set notification application
     * (one or many)
     *
     * @param $notifiable
     * @return mixed
     */
    public function via($notifiable);
}