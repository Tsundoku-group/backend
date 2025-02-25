<?php

namespace App\Service\Redis;

use App\Config\RedisClientConfig;

readonly class RedisMessageService
{
    public function __construct(private RedisClientConfig $redis)
    {
    }

    public function addMessageToConversation(int $conversationId, array $message): void
    {
        $messageJson = json_encode($message);
        $this->redis->getClient()->rpush((string) $conversationId, (array) $messageJson);
        $this->publishMessage($conversationId, $messageJson);
    }

    public function getMessagesFromConversation(string $conversationId): array
    {
        $messagesJson = $this->redis->getClient()->lrange($conversationId, 0, -1);
        $messages = [];

        foreach ($messagesJson as $messageJson) {
            $messages[] = json_decode($messageJson, true);
        }

        return $messages;
    }

    public function markMessagesRead(int $conversationId, string $userEmail): void
    {
        $currentMessages = $this->redis->getClient()->lrange((string) $conversationId, 0, -1);

        foreach ($currentMessages as $index => $messageJson) {
            $message = json_decode($messageJson, true);
            if (isset($message['isRead']) && !$message['isRead'] && $message['sender_email'] == $userEmail) {
                $message['isRead'] = true;
                $this->redis->getClient()->lset((string) $conversationId, $index, json_encode($message));
            }
        }
    }

    public function publishMessage(int $channel, string $message): void
    {
        $this->redis->getClient()->publish($channel, $message);
    }
}
