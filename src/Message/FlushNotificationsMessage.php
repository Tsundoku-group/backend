<?php

namespace App\Message;

class FlushNotificationsMessage
{
    private ?int $receiverId;

    public function __construct(?int $receiverId = null)
    {
        $this->receiverId = $receiverId;
    }

    public function getReceiverId(): ?int
    {
        return $this->receiverId;
    }
}
