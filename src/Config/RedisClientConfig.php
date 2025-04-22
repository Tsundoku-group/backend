<?php

namespace App\Config;

use Exception;
use Predis\Client;

class RedisClientConfig
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
            throw new Exception('Erreur de connexion à Redis', 500, $e);
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}