<?php

namespace App\Services;

use Exception;
use Predis\Client;

class ConfRedisService
{
    private Client $client;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $this->client = new Client($_ENV['REDIS_URL']);
            $this->client->connect();
        } catch (Exception $e) {
            throw new Exception('Impossible de se connecter à Redis: ' . $e->getMessage());
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function addMessageToConversation(int $conversationId, array $message): void
    {
        $messageJson = json_encode($message);
        $this->client->rpush($conversationId, $messageJson);
        $this->publishMessage($conversationId, $messageJson);
    }

    public function getMessagesFromConversation(string $conversationId): array
    {
        $messagesJson = $this->client->lrange($conversationId, 0, -1);
        $messages = [];

        foreach ($messagesJson as $messageJson) {
            $messages[] = json_decode($messageJson, true);
        }

        return $messages;
    }

    public function markMessagesRead(int $conversationId, string $userEmail): void
    {
        $currentMessages = $this->client->lrange($conversationId, 0, -1);

        foreach ($currentMessages as $index => $messageJson) {
            $message = json_decode($messageJson, true);
            if (isset($message['isRead']) && !$message['isRead'] && $message['sender_email'] == $userEmail) {
                $message['isRead'] = true;
                $this->client->lset($conversationId, $index, json_encode($message));
            }
        }
    }

    public function publishMessage(int $channel, string $message): void
    {
        $this->client->publish($channel, $message);
    }

    public function subscribeToChannel(int $channel, callable $callback): void
    {
        $this->client->subscribe([$channel], function ($message) use ($callback) {
            $callback($message->payload);
        });
    }
}