<?php

namespace App\Message;

class FlushNotificationsMessage
{
    private ?string $receiverId;

    public function __construct(?string $receiverId = null)
    {
        $this->receiverId = $receiverId;
    }

    public function getReceiverId(): ?string
    {
        return $this->receiverId;
    }
}