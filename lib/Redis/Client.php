<?php

namespace Redis;

use Redis\Client\ClientAdapter;
use Redis\Client\Adapter\Predis\ClientCreator;
use Redis\Client\Adapter\PredisClientAdapter;
use Redis\Exception\ConfigurationError;

/**
 * Class Client
 * Represents one single redis node and provides identification if we want to connect to it
 * @package Sentinel
 */
class Client extends AbstractClient{
    
    /**
     * Cosntructor
     * @param string $host
     * @param string $port
     * @param ClientAdapter $clientAdapter
     */
    public function __construct($host, $port, ClientAdapter $clientAdapter = null){
        
        $this->setHost($host);
        $this->setPort($port);

        if (null === $clientAdapter) {
            //throw new ConfigurationError();
            $clientAdapter = new PredisClientAdapter(new ClientCreator());
        }
        
        $this->clientAdapter = $this->initializeClient($clientAdapter);
    }

    /**
     * Initialises client
     * @param ClientAdapter $clientAdapter
     * @return ClientAdapter
     */
    protected function initializeClient(ClientAdapter $clientAdapter){
        $clientAdapter->setHost($this->getHost());
        $clientAdapter->setPort($this->getPort());

        return $clientAdapter;
    }

    /**
     * Checks if node is master
     * @return boolean
     */
    public function isMaster(){
        // Doesn't work with Redis < 2.8.12
        //return true;
        
        return $this->getRoleType() === self::ROLE_MASTER; 
    }

    /**
     * Checks if node is slave
     * @return boolean
     */
    public function isSlave(){
        return $this->getRoleType() === self::ROLE_SLAVE;
    }
}