<?php

namespace MkyCore\EventNotification;

class HasManyUnReadNotification extends \MkyCore\RelationEntity\HasMany
{

    public function markAsRead()
    {
        /** @var Notification[] $notifications */
        $notifications = $this->get();
        for ($i = 0; $i < count($notifications); $i++) {
            $notification = $notifications[$i];
            $notification->markAsRead();
        }
    }
}