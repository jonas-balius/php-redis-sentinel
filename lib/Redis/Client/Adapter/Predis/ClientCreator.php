<?php

namespace Redis\Client\Adapter\Predis;

class ClientCreator implements ClientFactory{

    /**
     * Creates predis client
     * @param array $parameters
     * @return \Predis\Client
     */
    public function createClient(array $parameters = array()){
        $predisClient = new \Predis\Client($parameters);
        $predisClient->getProfile()->defineCommand('role', '\\Redis\\Client\\Adapter\\Predis\\Command\\RoleCommand');

        return $predisClient;
    }
} 