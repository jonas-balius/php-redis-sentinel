<?php

namespace Redis\Client\Adapter;

use Redis\Client\AdapterInterface;
use Redis\Exception\ConnectionError;
use Redis\Exception\SentinelError;
use Redis\Exception\RoleError;

/**
 * Class SocketClientAdapter
 */
class SocketClientAdapter extends AbstractClientAdapter implements AdapterInterface{

    protected $socket;
    
    /**
     * Connects to server
     */
    public function connect(){
        if ( !$this->isConnected()){
            
            $this->socket = @fsockopen($this->getHost(), $this->getPort(), $errno, $errstr, 1);
            if ( !$this->socket) {
                throw new ConnectionError($errstr);
            }
            
            $this->isConnected = true;
        }
    }

    /**
     * Gets role
     * @return string
     */
    public function getRole(){
        
        return $this->getRoleInfo();
    }
    
    /**
     * Gets master
     * @param string $name - master name
     * @return array - first element is host and second is port
     */
    public function getMaster($name){
    
        $data = $this->getMasterAddress($name);
    
        if (isset($data[0]) && isset($data[1]) && !empty($data[0]) && !empty($data[1])){
            return $data;
        }
    
        throw new SentinelError('The sentinel does not know the master address');
    }
    
    /**
     * Gets master address
     * @param string $name - master name
     * @return array where first element is host and second port
     */
    private function getMasterAddress($name) {
        $this->connect();
        $this->write('SENTINEL get-master-addr-by-name '. $name);
        $this->write('QUIT');
        $data = $this->extract($this->get());
        $this->close();
        
        if ( isset($data[0]) && count($data[0]) === 1){
            return array(key($data[0]), current($data[0]));
        }
        
        throw new SentinelError('The sentinel does not know the master address');
    }
    
    private function getRoleInfo(){
        
        // TODO: implement role parsing
        return 'sentinel';
        
        $this->connect();
        $this->write('ROLE');
        $this->write('QUIT');
        $data = $this->extract($this->get());
        $this->close();
        
        if ( isset($data[0]) && count($data[0]) === 1){
            return array(key($data[0]), current($data[0]));
        }
        
        throw new RoleError('Can not get role');
    }
    
    /**
     * Gets masters
     * @return array
     */
//     public function getMasters() {
//         $this->connect();
//         $this->write('SENTINEL masters');
//         $this->write('QUIT');
//         $data = $this->extract($this->get());
//         $this->close();
//        
//         return $data;
//     }
    
    /**
     * Gets slaves for selected master
     * @param string $master
     * @return array
     */
//     public function getSlaves($master) {
//         $this->connect();
//         $this->write('SENTINEL slaves ' . $master);
//         $this->write('QUIT');
//         $data = $this->extract($this->get());
//         $this->close();
//        
//         return $data;
//     }
    
    /**
     * Pings server
     * @return bool
     */
//     public function ping() {
//         $this->connect();
//         $this->write('PING');
//         $this->write('QUIT');
//         $data = $this->get();
//         $this->close();
//    
//         return ($data === '+PONG');
//     }
    
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