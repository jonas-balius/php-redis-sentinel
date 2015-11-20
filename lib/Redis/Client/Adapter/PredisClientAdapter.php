<?php

namespace Redis\Client\Adapter;

use Redis\Client\Adapter\Predis\ClientFactory;
use Redis\Client\ClientAdapter;
use Redis\Client\Adapter\Predis\ClientCreator;

class PredisClientAdapter extends AbstractClientAdapter implements ClientAdapter{
    
    /**
     * Redis client
     * @var \Predis\Client
     */
    private $client;

    /**
     * @var \Redis\Client\Adapter\Predis\ClientFactory
     */
    private $clientFactory;

    /**
     * Constructor
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory = null){
        if (null === $clientFactory){
            $clientFactory = new ClientCreator();
        }
        
        $this->clientFactory = $clientFactory;
    }

    /**
     * Gets predis client
     * @return \Predis\Client
     */
    public function getClient(){
        if (empty($this->client)) {
            $this->connect();
        }

        return $this->client;
    }

    /**
     * Connects to client
     */
    public function connect(){
        $this->client = $this->clientFactory->createClient($this->getClientParameters());
        $this->client->connect();
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