<?php

namespace App\Config;

use Predis\Client;
use Exception;

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
            throw new Exception("Erreur de connexion à Redis", $e->getMessage(), 500);
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}