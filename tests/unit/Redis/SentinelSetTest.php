<?php

namespace Redis;

use Redis\Client\Adapter\PhpRedisClientAdapter;
use Redis\BackoffStrategy\Incremental;
use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\AdapterInterface as ClientAdapter;
use Redis\Client\Adapter\NullClientAdapter;
use Redis\Client\Factory as ClientFactory;

class SentinelSetTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetMasterName(){
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        
        $this->assertEquals('test-set', $sentinelSet->getName(), 'Unable to get master name');
        $sentinelSet->setName('new-master');
        $this->assertEquals('new-master', $sentinelSet->getName(), 'Unable to set/get master name');
    }
    
    public function testSetGetBackoffStrategy(){
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
    
        $backoffStrategy = new Incremental(1, 4);
        $sentinelSet->setBackoffStrategy($backoffStrategy);
        $this->assertEquals($backoffStrategy, $sentinelSet->getBackoffStrategy(), 'Unable to set/get backoff startegy');
        $this->assertAttributeInstanceOf('\\Redis\\BackoffStrategy\\Incremental', 'backoffStrategy', $sentinelSet, 'Not correct backoff strategy');
    }
    
    public function testSetClientFactory(){
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        $this->assertEquals(new ClientFactory(), $sentinelSet->getClientFactory(), 'Wrong default factory');
    }
    
    public function testClientAdapterSetCorrectly(){
        
        $adapter = new PhpRedisClientAdapter();
        $sentinelSet = new SentinelSet('test-set', $adapter);
        
        $this->assertEquals($adapter, $sentinelSet->getClientAdapter(), 'Unable to get client adapter');
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', 'clientAdapter', $sentinelSet, 'Not correct client adapter');
        
        $adapter = new NullClientAdapter();
        $sentinelSet->setClientAdapter($adapter);
        
        $this->assertEquals($adapter, $sentinelSet->getClientAdapter(), 'Unable to set/get client adapter');
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\NullClientAdapter', 'clientAdapter', $sentinelSet, 'Not correctly set client adapter');
    }
    
    public function testNoSentinelsInitially(){
        
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        $this->assertEquals(array(), $sentinelSet->getSentinels(), 'Initial sentinels not set correctly');
    }
    
    public function testAddSentinel(){
        
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        
        $sentinel1 = new ClientSentinel('10.1.1.1', 1111);
        
        $sentinelSet->addSentinel($sentinel1);
        $this->assertEquals(array($sentinel1), $sentinelSet->getSentinels(), 'Can not add sentinel');
        
        $sentinel2 = new ClientSentinel('10.2.2.2', 2222);
        
        $sentinelSet->addSentinel($sentinel2);
        $this->assertEquals(array($sentinel1, $sentinel2), $sentinelSet->getSentinels(), 'Can not add sentinel');
    }
    
    public function testGetMasterExceptionWhenNoSentinels(){
        
        $this->setExpectedException('\\Redis\\Exception\\ConfigurationError', 'You need to configure and add sentinel nodes before attempting to fetch a master');
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        $sentinelSet->getMaster();
    }
    
    public function testGetMasterNoReachableSentinels(){
        
        $sentinel1 = $this->createSentinelClientMock('127.0.0.2', 2121, null, false, '127.0.0.10', 6370);
        $sentinel2 = $this->createSentinelClientMock('127.0.0.3', 2222, null, false, '127.0.0.10', 6370);
        
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
        
        $this->setExpectedException('\\Redis\\Exception\\ConnectionError', 'All sentinels are unreachable');
        $sentinelSet->getMaster();
    }
    
    public function testGetMasterFailsIfMasterSteppedDown(){
    
        $sentinel1 = $this->createSentinelClientMock('127.0.0.2', 2121, null, false, '127.0.0.11', 6371);
        $sentinel2 = $this->createSentinelClientMock('127.0.0.3', 2222, null, true, '127.0.0.10', 6370);
    
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        $sentinelSet->setClientFactory($this->createClientFactoryMock('127.0.0.10', 6370, true, false)); // last false means that it is not master anymore
    
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
    
        $this->setExpectedException('\\Redis\\Exception\\RoleError', 'Only a node with role master may be returned (maybe the master was stepping down during connection?');
        $sentinelSet->getMaster();
    }
    
    public function testGetMasterSuccess(){
    
        $sentinel1 = $this->createSentinelClientMock('127.0.0.2', 2121, null, false, '127.0.0.11', 6371);
        $sentinel2 = $this->createSentinelClientMock('127.0.0.3', 2222, null, true, '127.0.0.10', 6370);
    
        $sentinelSet = new SentinelSet('test-set', new PhpRedisClientAdapter());
        $sentinelSet->setClientFactory($this->createClientFactoryMock('127.0.0.10', 6370, true, true));
        
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
    
        $masterNode = $sentinelSet->getMaster();
        
        $this->assertTrue($masterNode instanceof Client);
        $this->assertEquals('127.0.0.10', $masterNode->getHost(), 'Incorrect master host');
        $this->assertEquals(6370, $masterNode->getPort(), 'Incorrect master port');
    }
    
    /**
     * Creates sentinel client mock
     * @param string $host - host
     * @param string $port - port
     * @param ClientAdapter $adapter
     * @param bool $isConnected - connected or not
     * @param bool $isMaster - is client master or not
     * @param string $masterHost - host
     * @param string $masterPort - port
     * @return SentinelClient
     */
    private function createSentinelClientMock($host, $port, $adapter, $isConnected, $masterHost = null, $masterPort = null){
    
        if (is_null($adapter)){
            $adapter = new PhpRedisClientAdapter();
        }
        
        $client = $this->getMockBuilder('\Redis\ClientSentinel')
            ->setConstructorArgs(array($host, $port, $adapter))
            ->setMethods(array('connect', 'isConnected', 'getMaster'))
            ->getMock();
    
        if ($isConnected){
            $client->expects($this->any())
                ->method('connect')
                ->will($this->returnValue(null));
        }
        else{
            $client->expects($this->any())
                ->method('connect')
                ->will($this->throwException(new ConnectionError('Unable to connect to redis at '. $client->getHost(). ':'. $client->getPort())));
        }
    
        $client->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue($isConnected));
        
        $client->expects($this->any())
            ->method('getMaster')
            ->will($this->returnValue(array($masterHost, $masterPort)));

        return $client;
    }
    
    /**
     * Creates client factory mock
     * @param string $host - host
     * @param string $port - port
     * @param bool $isConnected - connected or not
     * @param bool $isMaster - is client master or not
     * @return ClientFactory
     */
    private function createClientFactoryMock($host, $port, $isConnected, $isMaster){
        
        $factory = $this->getMockBuilder('\Redis\Client\Factory')
            ->setMethods(array('createClient'))
            ->getMock();
        
        $factory->expects($this->any())
            ->method('createClient')
            ->will($this->returnValue($this->createRedisClientMock($host, $port, null, $isConnected, $isMaster)));

        return $factory;
    }
    
    /**
     * Creates redis client mock
     * @param string $host - host
     * @param string $port - port
     * @param ClientAdapter $adapter
     * @param bool $isConnected - connected or not
     * @param bool $isMaster - is client master or not
     * @return SentinelClient
     */
    private function createRedisClientMock($host, $port, $adapter, $isConnected, $isMaster){
    
        if (is_null($adapter)){
            $adapter = new PhpRedisClientAdapter();
        }
    
        $client = $this->getMockBuilder('\Redis\Client')
            ->setConstructorArgs(array($host, $port, $adapter))
            ->setMethods(array('connect', 'isConnected', 'isMaster', 'getRole'))
            ->getMock();
    
        if ($isConnected){
            $client->expects($this->any())
                ->method('connect')
                ->will($this->returnValue(null));
        }
        else{
            $client->expects($this->any())
                ->method('connect')
                ->will($this->throwException(new ConnectionError('Unable to connect to redis at '. $client->getHost(). ':'. $client->getPort())));
        }
    
        $client->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue($isConnected));
        
        $client->expects($this->any())
            ->method('isMaster')
            ->will($this->returnValue($isMaster));
        
        $client->expects($this->any())
            ->method('isMaster')
            ->will($this->returnValue($isMaster));
        
        $client->expects($this->any())
            ->method('getRole')
            ->will($this->returnValue($isMaster? 'master': 'slave'));
    
        return $client;
    }
}