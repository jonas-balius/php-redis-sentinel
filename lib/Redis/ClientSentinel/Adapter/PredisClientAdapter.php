<?php

namespace Redis\ClientSentinel\Adapter;

use Redis\ClientSentinel\Adapter\Predis\Command\SentinelCommand;
use Redis\ClientSentinel\Adapter\Predis\ClientFactory as SentinelFactory;
use Redis\ClientSentinel\ClientSentinelAdapter as SentinelAdapter;
use Redis\Client\Adapter\Predis\ClientFactory as RedisFactory;
use Redis\Client\Adapter\PredisClientAdapter as RedisClientAdapter;
use Redis\Client;
use Redis\Exception\SentinelError;

class PredisClientAdapter extends AbstractClientAdapter implements SentinelAdapter{

    /**
     * Predis client
     * @var \Predis\Client
     */
    private $predisClient;

    /**
     * Sentinel factory
     * @var SentinelFactory
     */
    private $sentinelFactory;
    
    /**
     * Redis factory
     * @var RedisFactory
     */
    private $redisFactory;

    /**
     * Constructor
     * @param SentinelFactory $sentinelFactory
     * @param RedisFactory $redisFactory
     */
    public function __construct(SentinelFactory $sentinelFactory, RedisFactory $redisFactory){
        $this->sentinelFactory = $sentinelFactory;
        $this->redisFactory = $redisFactory;
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
        $this->predisClient = $this->sentinelFactory->createClient($this->getClientParameters());
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
    
    /**
     * Gets master
     * @param string $name - master name
     * @return \Redis\Client
     */
    public function getMaster($name){
    
        list($host, $port) = $this->getClient()->sentinel(SentinelCommand::GETMASTER, $name);
    
        if (!empty($host) && !empty($port)) {
            $master = new Client($host, $port, new RedisClientAdapter($this->redisFactory));
            return $master;
        }
    
        throw new SentinelError('The sentinel does not know the master address');
    }
}