<?php

namespace Redis\Client\Adapter;

use Redis\Client\ClientAdapter;
use Redis\Client;

/**
 * Class SocketSentinelClientAdapter
 *
 * The null client is being used to test whether the sentinel client accepts multiple adapter by having another pretty
 * useless one that conforms to the SentinelClientAdapter interface.  As soon as we have another client library supported
 * we need to remove the null adapter again
 *
 * @package Sentinel\Client\Adapter
 */
class SocketClientAdapter extends AbstractClientAdapter implements ClientAdapter{

    protected $socket;

    /**
     * Connects to server
     */
    public function connect(){
        if ( !$this->isConnected()){
            
            $this->socket = @fsockopen($this->getHost(), $this->getPort(), $errno, $errstr, 1);
            if ( !$this->socket) {
                throw new ConnectionTcpExecption($errstr);
            }
            
            $this->isConnected = true;
        }
    }

    public function getRole(){
        return Client::ROLE_SENTINEL;
    }
    
    /**
     * Pings server
     * @return bool
     */
    public function ping() {
        $this->connect();
        $this->write('PING');
        $this->write('QUIT');
        $data = $this->get();
        $this->close();
        
        return ($data === '+PONG');
    }
    
    /**
     * Gets masters
     * @return TODO
     */
    public function masters() {
        $this->connect();
        $this->write('SENTINEL masters');
        $this->write('QUIT');
        $data = $this->extract($this->_get());
        $this->close();
        
        return $data;
    }
    
    /**
     * Gets slaves for selected master
     * @param string $master
     * @return TODO
     */
    public function getSlaves($master) {
        $this->connect();
        $this->write('SENTINEL slaves ' . $master);
        $this->write('QUIT');
        $data = $this->extract($this->_get());
        $this->close();
        
        return $data;
    }
    
    /**
     * Writes command to socket
     * @param strin $command
     * @return TODO
     */
    protected function write($command) {
        return fwrite($this->socket, $command. "\r\n");
    }

    /**
     * Gets response from socket
     * @return string
     */
    protected function get(){
        $buffer = '';
        while($this->receiving()){
            $buffer .= fgets($this->socket);
        }
        
        return rtrim($buffer, "\r\n+OK\n");
    }
    
    /**
     * Reads data from socket
     * @return string
     */
    protected function receiving() {
        return !feof($this->socket);
    }
    
    /**
     * Extracts data
     * @param string $data
     * @return array
     */
    protected function extract($data) {
        if (!$data){ 
            return array();
        }
        
        $lines = explode("\r\n", $data);
        $isRoot = $isChild = false;
        $count = count($lines);
        
        $results = $current = array();
        for ($i = 0; $i < $count; $i++) {
            $str = $lines[$i];
            $prefix = substr($str, 0, 1);
            if ($prefix === '*') {
                if (!$isRoot) {
                    $isRoot = true;
                    $current = array();
                    continue;
                } else if (!$isChild) {
                    $isChild = true;
                    continue;
                } else {
                    $isRoot = $isChild = false;
                    $results[] = $current;
                    continue;
                }
            }
            
            $keylen         = $lines[$i++];
            $key            = $lines[$i++];
            $vallen         = $lines[$i++];
            $val            = $lines[$i++];
            $current[$key]  = $val;
            --$i;
        }
        
        $results[] = $current;
        
        return $results;
    }
    
    /**
     * Closes connection
     */
    protected function close() {
        $ret = @fclose($this->socket);
        $this->socket = null;
        return $ret;
    }
    
    public function __destruct() {
        if ($this->socket)
            $this->close();
    }
}