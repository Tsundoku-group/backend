<?php

namespace App\Service;

use App\Config\RedisClientConfig;

class ResetThresholdService
{
    public function __construct(private RedisClientConfig $redis)
    {
    }

    public function resetThresholds(): void
    {
        $keys = $this->redis->getClient()->keys('notification_flush_threshold:*');
        foreach ($keys as $key) {
            $this->redis->getClient()->set($key, 10);
        }
    }
}
