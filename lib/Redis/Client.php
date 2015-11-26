<?php

namespace Redis;

use Redis\Client\Adapter\PredisClientAdapter;
use Redis\Client\AbstractClient;
use Redis\Exception\ConfigurationError;

/**
 * Class Client
 * Represents one single redis node and provides identification if we want to connect to it
 * @package Sentinel
 */
class Client extends AbstractClient{

    /**
     * Checks if node is master
     * @return boolean
     */
    public function isMaster(){
        // Doesn't work with Redis < 2.8.12
        //return true;
        
        return $this->getRole() === self::ROLE_MASTER; 
    }

    /**
     * Checks if node is slave
     * @return boolean
     */
    public function isSlave(){
        return $this->getRole() === self::ROLE_SLAVE;
    }
}