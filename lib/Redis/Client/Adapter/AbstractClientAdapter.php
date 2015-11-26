<?php

namespace Redis\Client\Adapter;

use Redis\Client\AdapterInterface;

abstract class AbstractClientAdapter implements AdapterInterface{
    
    protected $host;
    protected $port;
    protected $isConnected = false;

    public function setHost($host){
        $this->host = $host;
    }

    public function setPort($port){
        $this->port = $port;
    }

    public function isConnected(){
        return $this->isConnected;
    }
    
    public function getHost() {
        return $this->host;
    }
    
    public function getPort() {
        return $this->port;
    }
} 