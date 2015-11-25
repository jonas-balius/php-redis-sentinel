<?php

namespace Redis;

use Redis\Client\ClientAdapter;
//use Redis\Exception\InvalidProperty;
//use Symfony\Component\Validator\Constraints\Collection;
//use Symfony\Component\Validator\Constraints\Ip;
//use Symfony\Component\Validator\Constraints\Range;
//use Symfony\Component\Validator\Validation;

/**
 * Class Client
 * Represents one single sentinel node and provides identification if we want to connect to it
 * @package Sentinel
 */
abstract class AbstractClient{

    const ROLE_SENTINEL = 'sentinel';
    const ROLE_MASTER   = 'master';
    const ROLE_SLAVE    = 'slave';
    
    /**
     * Host
     * @var string
     */
    protected $host;

    /**
     * Port
     * @var integer
     */
    protected $port;

    /**
     * Client adapter
     * @var ClientAdapter
     */
    protected $clientAdapter;

    /**
     * Sets host
     * @param string $host
     */
    protected function setHost($host){
        //$this->guardThatHostFormatIsValid($host);
        $this->host = $host;
    }
    
    /**
     * Gets host
     * @return string
     */
    public function getHost(){
        return $this->host;
    }
    
    /**
     * Sets port
     * @param string $port
     */
    protected function setPort($port){
        //$this->guardThatServerPortIsValid($port);
        $this->port = $port;
    }

    /**
     * Gets port
     * @return int
     */
    public function getPort(){
        return $this->port;
    }

    /**
     * Validates that the proper IP address format is used when constructing the sentinel node
     * @param $host
     * @throws Exception\InvalidProperty
     */
//     protected function guardThatHostFormatIsValid($host){
//         $ipValidator = Validation::createValidator();
//         $violations = $ipValidator->validateValue($host, new Ip());
//         if ($violations->count() > 0) {
//             throw new InvalidProperty('A sentinel node requires a valid IP address');
//         }
//     }

    /**
     * @param $port
     * @throws Exception\InvalidProperty
     */
//     protected function guardThatServerPortIsValid($port){
//         $validator = Validation::createValidator();
//         $violations = $validator->validateValue($port, new Range(array('min' => 0, 'max' => 65535)));
//         if ($violations->count() > 0) {
//             throw new InvalidProperty('A sentinel node requires a valid service port');
//         }
//     }

    /**
     * Connects
     */
    public function connect(){
        $this->clientAdapter->connect();
    }

    /**
     * Checks if connected
     * @return boolean
     */
    public function isConnected(){
        return $this->clientAdapter->isConnected();
    }

    /**
     * Gets client adapter
     * @return ClientAdapter
     */
    public function getClientAdapter(){
        return $this->clientAdapter;
    }

    /**
     * Gets role
     * @return string
     */
    public function getRole(){
        return $this->clientAdapter->getRole();
    }
}