<?php

namespace Redis\Client\Adapter;

use Redis\Client\Adapter\Predis\ClientFactory;
use Redis\Client\ClientAdapter;

class PhpRedisClientAdapter extends AbstractClientAdapter implements ClientAdapter{
    
    /**
     * Predis client
     * @var \Predis\Client
     */
    private $predisClient;

    /**
     * @var \Redis\Client\Adapter\Predis\ClientFactory
     */
    private $clientFactory;

    /**
     * Constructor
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory){
        $this->clientFactory = $clientFactory;
    }

    /**
     * Gets predis client
     * @return \Predis\Client
     */
    public function getClient(){
        if (empty($this->predisClient)) {
            $this->connect();
        }

        return $this->predisClient;
    }

    /**
     * Connects to client
     */
    public function connect(){
        $this->predisClient = $this->clientFactory->createClient($this->getClientParameters());
        $this->predisClient->connect();
    }

    /**
     * Gets client parameters
     * @return array
     */
    private function getClientParameters(){
        return array(
            'scheme'    => 'tcp',
            'host'      => $this->host,
            'port'      => $this->port,
        );
    }

    /**
     * Gets role
     * @return string
     */
    public function getRole(){
        return $this->getClient()->role();
    }
}