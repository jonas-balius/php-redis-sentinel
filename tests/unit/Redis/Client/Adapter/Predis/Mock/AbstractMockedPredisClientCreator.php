<?php

namespace Redis\Client\Adapter\Predis\Mock;

use Redis\Client;

abstract class AbstractMockedPredisClientCreator
{
    public function createClient($clientType, array $parameters = array())
    {
        switch($clientType)
        {
            case Client::TYPE_REDIS:
                return $this->createRedisClient($parameters);
            case Client::TYPE_SENTINEL:
                return $this->createSentinelClient($parameters);
        }
    }
} 