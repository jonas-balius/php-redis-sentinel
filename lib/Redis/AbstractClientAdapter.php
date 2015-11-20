<?php

namespace Redis;

interface AbstractClientAdapter {

    /**
     * sets host
     * @param string $host
     */
    public function setHost($host);
    
    /**
     * Sets port
     * @param integer $port
     */
    public function setPort($port);
    
    /**
     * Connects
     */
    public function connect();
    
    /**
     * Checks if is connected
     * @return bool
     */
    public function isConnected();
    
    /**
     * Gets role
     * @return string
     */
    public function getRole();
} 