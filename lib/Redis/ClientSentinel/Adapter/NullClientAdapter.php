<?php

namespace Redis\ClientSentinel\Adapter;

use Redis\ClientSentinel\ClientSentinelAdapter;
use Redis\Client;

/**
 * Class NullSentinelClientAdapter
 *
 * The null client is being used to test whether the sentinel client accepts multiple adapter by having another pretty
 * useless one that conforms to the SentinelClientAdapter interface.  As soon as we have another client library supported
 * we need to remove the null adapter again
 *
 * @package Sentinel\Client\Adapter
 */
class NullClientAdapter extends AbstractClientAdapter implements ClientSentinelAdapter{
    
    public function connect(){
        $this->isConnected = true;
    }

    /**
     * Gets master
     * @param string $name - master name
     * @return array - first element is host and second is port
     */
    public function getMaster($name){
        return array('127.0.0.1', 6380);
    }

    public function getRole(){
        return Client::ROLE_MASTER;
    }
}