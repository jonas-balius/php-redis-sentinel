<?php

namespace Redis\Client\Adapter\Predis;

interface ClientFactory{
    
    /**
     * Creates predis client
     * @param array $parameters
     * @return \Predis\Client
     */
    public function createClient(array $parameters = array());
} 