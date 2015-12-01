<?php

namespace Redis\Client;

use Redis\Client\AdapterInterface as ClientAdapter;
use Redis\Client;
use Redis\ClientSentinel;
use Redis\Client\AbstractClient;

class Factory{

    const   TYPE_REDIS = 'redis',
            TYPE_SENTINEL = 'sentinel';
    
    /**
     * Creates a client
     * @param string $host
     * @param string $port
     * @param ClientAdapter $adapter
     * @param string $type
     * @return AbstractClient
     */
    public function createClient($host, $port, ClientAdapter $adapter, $type = self::TYPE_REDIS){
        
        switch ($type) {
            case self::TYPE_SENTINEL:
                $client = new ClientSentinel($host, $port, $adapter);
            break;
            case self::TYPE_REDIS:
            default:
                $client = new Client($host, $port, $adapter);
            break;
        }
        
        return $client;
    }
} 