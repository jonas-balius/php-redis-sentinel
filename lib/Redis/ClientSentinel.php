<?php

namespace Redis;

use Redis\ClientSentinel\Adapter\PredisClientAdapter;
use Redis\Client\AbstractClient;
use Redis\Exception\ConfigurationError;

/**
 * Class Client
 * Represents one single sentinel node and provides identification if we want to connect to it
 * @package Sentinel
 */
class ClientSentinel extends AbstractClient{

    /**
     * Gets master
     * @return array
     */
    public function getMaster($name){
        return $this->clientAdapter->getMaster($name);
    }

    /**
     * Checks if node is sentinel
     * @return boolean
     */
    public function isSentinel(){
        return $this->getRole() === self::ROLE_SENTINEL;
    }
}