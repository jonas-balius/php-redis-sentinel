<?php

namespace Redis\ClientSentinel\Adapter;

use Redis\ClientSentinel\ClientSentinelAdapter;

class PhpRedisClientAdapter extends AbstractClientAdapter implements ClientSentinelAdapter{
    
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
        
        return array($info['role']);
    }
    
    /**
     * Gets master. PhpRedis does not support sentinel commands in v2.7, so we get master from info
     * @param string $name - master name
     * @return array - first element is host and second is port
     */
    public function getMaster($name){
        
        $info = $this->getClient()->info();
        
        $i = 0;
        while (isset($info['master'. $i])) {
            $params = $this->extract($info['master'. $i]);
            
            if (isset($params['name']) && $params['name'] === $name && isset($params['address'])){
                return array($params['address']['host'], $params['address']['port']);
            }
        }
        
        throw new SentinelError('The sentinel does not know the master address');
    }
    
    /**
     * Extracts data from master string info
     * @param string $data
     * @return array
     */
    protected function extract($data) {
        
        $extracted = array();
        $params = explode(',', $data);
        foreach ($params as $param) {
            $value = explode('=', $param);
            if (isset($value[0]) && isset($value[1])) {
                if (false !== strpos($value[1], ':')) {
                    $value1 = explode(':', $value[1]);
                    $extracted[$value[0]] = array('host' => $value1[0], 'port' => $value1[1]);
                }
                else{
                    $extracted[$value[0]] = $value[1];
                }
            }
        }
        
        return $extracted;
    }
}