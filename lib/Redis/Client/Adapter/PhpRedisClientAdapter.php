<?php

namespace Redis\Client\Adapter;

use Redis\Client\ClientAdapter;

class PhpRedisClientAdapter extends AbstractClientAdapter implements ClientAdapter{
    
    /**
     * PhpRedis client
     * @var \Predis\Client
     */
    private $client;

    /**
     * Constructor
     */
    public function __construct(){
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
        $this->client = new \Redis();
        $this->client->connect($this->getHost(), $this->getPort());
    }

    /**
     * Gets role
     * @return string
     */
    public function getRole(){
        
        $info = $this->getClient()->info();
        return $info['role'];
    }
}