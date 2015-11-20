<?php

namespace Redis\ClientSentinel\Adapter;

use Redis\ClientSentinel\ClientSentinelAdapter as SentinelAdapter;
use Redis\ClientSentinel\Adapter\Predis\ClientFactory;
use Redis\ClientSentinel\Adapter\Predis\ClientCreator;
use Redis\ClientSentinel\Adapter\Predis\Command\SentinelCommand;
use Redis\Exception\SentinelError;

class PredisClientAdapter extends AbstractClientAdapter implements SentinelAdapter{

    /**
     * Redis client
     * @var \Predis\Client
     */
    private $client;

    /**
     * Sentinel factory
     * @var ClientFactory
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

    /**
     * Gets master
     * @param string $name - master name
     * @return array - first element is host and second is port
     */
    public function getMaster($name){
    
        $data = $this->getClient()->sentinel(SentinelCommand::GETMASTER, $name);
        
        if (isset($data[0]) && isset($data[1]) && !empty($data[0]) && !empty($data[1])){
            return $data;
        }
    
        throw new SentinelError('The sentinel does not know the master address');
    }
}