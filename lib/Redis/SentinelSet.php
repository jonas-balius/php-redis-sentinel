<?php

namespace Redis;

use Redis\Client\ClientAdapter;
use Redis\BackoffStrategy\None;
use Redis\BackoffStrategy;
use Redis\Exception\ConfigurationError;
use Redis\Exception\ConnectionError;
use Redis\Exception\RoleError;

//use Redis\Exception\InvalidProperty;
//use Symfony\Component\Validator\Constraints\NotBlank;
//use Symfony\Component\Validator\Validation;

/**
 * Class SentinelSet
 * Represents a set of sentinel nodes that are monitoring a master with it's slaves
 * @package Sentinel
 */
class SentinelSet{

    /**
     * Master name
     * @var string
     */
    protected $name;

    /**
     * Sentinels
     * @var array
     */
    protected $sentinels = array();

    /**
     * Client adapter
     * @var ClientAdapter
     */
    protected $clientAdapter;
    
    /**
     * Back of strategy
     * @var Client\BackoffStrategy\None
     */
    protected $backoffStrategy;

    /**
     * Constructor
     * @param string $name
     * @param ClientAdapter $clientAdapter
     * @param BackoffStrategy $backoffStrategy
     */
    public function __construct($name, ClientAdapter $clientAdapter, BackoffStrategy $backoffStrategy = null){
        
        if (null === $clientAdapter){
            throw new ConfigurationError('Client adapter not set');
        }
        
        if (null === $backoffStrategy){
            $backoffStrategy = new None(); // by default we don't implement a backoff
        }
        
        $this->setName($name);
        $this->setBackoffStrategy($backoffStrategy);
        $this->setClientAdapter($clientAdapter);
    }

    /**
     * Sets master name
     * @param string $name
     */
    public function setName($name){
        //$this->guardThatTheNameIsNotBlank($name);
        $this->name = $name;
    }
    
    /**
     * Gets master name
     * @return string
     */
    public function getName(){
        return $this->name;
    }
    
    /**
     * Sets backoff strategy
     * @param BackoffStrategy $backoffStrategy
     */
    public function setBackoffStrategy(BackoffStrategy $backoffStrategy){
        $this->backoffStrategy = $backoffStrategy;
    }
    
    /**
     * Gets backoff strategy
     * @return BackoffStrategy $backoffStrategy
     */
    public function getBackoffStrategy(){
        return $this->backoffStrategy;
    }
    
    /**
     * Sets client adapter
     * @param ClientAdapter $clientAdapter
     */
    public function setClientAdapter(ClientAdapter $clientAdapter){
        $this->clientAdapter = $clientAdapter;
    }
    
    /**
     * Gets client adapter
     * @return ClientAdapter $clientAdapter
     */
    public function getClientAdapter(){
        return $this->clientAdapter;
    }
    
    /**
     * Adds sentinel
     * @param ClientSentinel $sentinelClient
     */
    public function addSentinel(ClientSentinel $sentinelClient){
        $this->sentinels[] = $sentinelClient;
    }

    /**
     * Gets sentinels
     * @return SplFixedArray
     */
    public function getSentinels(){
        return \SplFixedArray::fromArray($this->sentinels);
    }

    /**
     * Gets master
     * @return Client
     * @throws Exception\ConnectionError
     * @throws Exception\ConfigurationError
     */
    public function getMaster(){
        if ($this->getSentinels()->count() == 0) {
            throw new ConfigurationError('You need to configure and add sentinel nodes before attempting to fetch a master');
        }

        do {
            try {
                foreach ($this->getSentinels() as $sentinelClient) {
                    /** @var $sentinelClient ClientSentinel */
                    try {
                        $sentinelClient->connect();
                        list($masterHost, $masterPort) = $sentinelClient->getMaster($this->getName());
                        
                        $redisClient = new Client($masterHost, $masterPort, $this->getClientAdapter());

                        if ($redisClient->isMaster()) {
                            return $redisClient;
                        } else {
                            throw new RoleError('Only a node with role master may be returned (maybe the master was stepping down during connection?)');
                        }
                    } catch (ConnectionError $e) {
                        // on error, try to connect to next sentinel
                    }
                }
            } catch (RoleError $e) {

                if ($this->backoffStrategy->shouldWeTryAgain()) {
                    usleep($this->backoffStrategy->getBackoffInMicroSeconds());
                } else {
                    throw $e;
                }
            }
        } while ($this->backoffStrategy->shouldWeTryAgain());

        throw new ConnectionError('All sentinels are unreachable');
    }

    /**
     * @param $name
     * @throws Exception\InvalidProperty
     */
    //     private function guardThatTheNameIsNotBlank($name){
    //         $validator = Validation::createValidator();
    //         $violations = $validator->validateValue($name, new NotBlank());
    //         if ($violations->count() > 0) {
    //             throw new InvalidProperty('A monitor set needs a valid name');
    //         }
    //     }
}