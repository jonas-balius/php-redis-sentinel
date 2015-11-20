<?php

namespace Redis;

use Redis\ClientSentinel\ClientSentinelAdapter as SentinelAdapter;
use Redis\ClientSentinel\Adapter\Predis\ClientCreator as SentinelCreator;
use Redis\Client\Adapter\Predis\ClientCreator as RedisCreator;
use Redis\ClientSentinel\Adapter\PredisClientAdapter;
use Redis\Exception\ConfigurationError;

/**
 * Class Client
 * Represents one single sentinel node and provides identification if we want to connect to it
 * @package Sentinel
 */
class ClientSentinel extends AbstractClient{

    /**
     * Constructor
     * @param string $host
     * @param string $port
     * @param SentinelAdapter $clientAdapter
     */
    public function __construct($host, $port, SentinelAdapter $clientAdapter = null){

        $this->setHost($host);
        $this->setPort($port);

        if (null === $clientAdapter) {
            //throw new ConfigurationError();
            $clientAdapter = new PredisClientAdapter(new SentinelCreator(), new RedisCreator());
        }
        
        $this->clientAdapter = $this->initializeClient($clientAdapter);
    }

    /**
     * Initialises client
     * @param SentinelAdapter $clientAdapter
     * @return SentinelAdapter
     */
    protected function initializeClient(SentinelAdapter $clientAdapter){
        $clientAdapter->setHost($this->getHost());
        $clientAdapter->setPort($this->getPort());

        return $clientAdapter;
    }

    /**
     * Gets master
     * @return \Redis\Client
     */
    public function getMaster($name){
        return $this->clientAdapter->getMaster($name);
    }

    /**
     * Checks if node is sentinel
     * @return boolean
     */
    public function isSentinel(){
        return $this->getRoleType() === self::ROLE_SENTINEL;
    }
}