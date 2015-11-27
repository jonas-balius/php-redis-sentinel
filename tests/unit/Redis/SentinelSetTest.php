<?php

namespace Redis;

//require_once __DIR__ . '/Client/Adapter/Predis/Mock/MockedPredisClientCreatorWithNoMasterAddress.php';

use Redis\Client\Adapter\Predis\Mock\MockedPredisClientCreatorWithNoMasterAddress;
use Redis\BackoffStrategy\Incremental;
use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\PhpRedisClientAdapter;
use Redis\Client\Adapter\NullClientAdapter;
use Predis\Client;

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
    
        
//     private $sentinelSetName = 'name-of-monitor-set';

//     private $onlineSentinelHost = '127.0.0.1';
//     private $onlineSentinelPort = 2424;

//     private $onlineMasterHost = '198.100.10.1';
//     private $onlineMasterPort = 5050;

//     private $onlineSteppingDownMasterHost = '198.100.10.1';
//     private $onlineSteppingDownMasterPort = 5050;

//     private $offlineSentinelHost = '127.0.0.1';
//     private $offlineSentinelPort = 2323;

//     protected function setUp()
//     {
//         $this->markTestSkipped('Test is not implemented. it has to be rewritten for phpredis');
//     } 
    
//     /**
//      * @return \Redis\Client
//      */
//     private function mockOnlineSentinel()
//     {
//         $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithNoMasterAddress(), Client::TYPE_SENTINEL);

//         $redisClient = \Phake::mock('\\Redis\Client');
//         \Phake::when($redisClient)->getHost()->thenReturn($this->onlineMasterHost);
//         \Phake::when($redisClient)->getPort()->thenReturn($this->onlineMasterPort);
//         \Phake::when($redisClient)->isMaster()->thenReturn(true);
//         \Phake::when($redisClient)->getRole()->thenReturn(Client::ROLE_MASTER);

//         $sentinelClient = \Phake::mock('\\Redis\\Client');
//         \Phake::when($sentinelClient)->connect()->thenReturn(null);
//         \Phake::when($sentinelClient)->getHost()->thenReturn($this->onlineSentinelHost);
//         \Phake::when($sentinelClient)->getPort()->thenReturn($this->onlineSentinelPort);
//         \Phake::when($sentinelClient)->getClientAdapter()->thenReturn($clientAdapter);
//         \Phake::when($sentinelClient)->getMaster(\Phake::anyParameters())->thenReturn($redisClient);

//         return $sentinelClient;
//     }

//     /**
//      * @return \Redis\Client
//      */
//     private function mockOfflineSentinel()
//     {
//         $sentinelClient = \Phake::mock('\\Redis\\Client');
//         \Phake::when($sentinelClient)->connect()->thenThrow(
//             new ConnectionError(sprintf('Could not connect to sentinel at %s:%d', $this->offlineSentinelHost, $this->offlineSentinelPort))
//         );
//         \Phake::when($sentinelClient)->getHost()->thenReturn($this->offlineSentinelHost);
//         \Phake::when($sentinelClient)->getPort()->thenReturn($this->offlineSentinelPort);

//         return $sentinelClient;
//     }

//     private function mockOnlineSentinelWithMasterSteppingDown()
//     {
//         $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithNoMasterAddress(), Client::TYPE_SENTINEL);

//         $masterNodeSteppingDown = \Phake::mock('\\Redis\Client');
//         \Phake::when($masterNodeSteppingDown)->getHost()->thenReturn($this->onlineSteppingDownMasterHost);
//         \Phake::when($masterNodeSteppingDown)->getPort()->thenReturn($this->onlineSteppingDownMasterPort);
//         \Phake::when($masterNodeSteppingDown)->isMaster()->thenReturn(false);

//         $masterNode = \Phake::mock('\\Redis\Client');
//         \Phake::when($masterNode)->getHost()->thenReturn($this->onlineMasterHost);
//         \Phake::when($masterNode)->getPort()->thenReturn($this->onlineMasterPort);
//         \Phake::when($masterNode)->isMaster()->thenReturn(true);

//         $sentinelClient = \Phake::mock('\\Redis\\Client');
//         \Phake::when($sentinelClient)->connect()->thenReturn(null);
//         \Phake::when($sentinelClient)->getHost()->thenReturn($this->onlineSentinelHost);
//         \Phake::when($sentinelClient)->getPort()->thenReturn($this->onlineSentinelPort);
//         \Phake::when($sentinelClient)->getClientAdapter()->thenReturn($clientAdapter);
//         \Phake::when($sentinelClient)->getMaster(\Phake::anyParameters())
//             ->thenReturn($masterNodeSteppingDown)
//             ->thenReturn($masterNode);

//         return $sentinelClient;
//     }


//     public function testThatMasterCannotBeFoundIfWeCannotConnectToSentinels()
//     {
//         $this->setExpectedException('\\Redis\\Exception\\ConnectionError', 'All sentinels are unreachable');
//         $sentinel1 = $this->mockOfflineSentinel();
//         $sentinel2 = $this->mockOfflineSentinel();
//         $sentinelSet = new SentinelSet('all-fail');
//         $sentinelSet->addSentinel($sentinel1);
//         $sentinelSet->addSentinel($sentinel2);
//         $sentinelSet->getMaster();
//     }

//     public function testThatSentinelNodeIsReturnedOnSuccessfulMasterDiscovery()
//     {
//         $noBackoff = new Incremental(0, 1);
//         $noBackoff->setMaxAttempts(1);

//         $sentinel1 = $this->mockOfflineSentinel();
//         $sentinel2 = $this->mockOnlineSentinel();

//         $sentinelSet = new SentinelSet('online-sentinel');
//         $sentinelSet->setBackoffStrategy($noBackoff);
//         $sentinelSet->addSentinel($sentinel1);
//         $sentinelSet->addSentinel($sentinel2);
//         $masterNode = $sentinelSet->getMaster();

//         $this->assertInstanceOf('\\Redis\\Client', $masterNode, 'The master returned should be an instance of \\Redis\\Client');
//         $this->assertEquals($this->onlineMasterHost, $masterNode->getHost(), 'The master node IP address returned should be the one of the online sentinel');
//         $this->assertEquals($this->onlineMasterPort, $masterNode->getPort(), 'The master node IP port returned should be the one of the online sentinel');
//     }

//     public function testThatMasterStatusOfANodeIsCheckedAfterConnecting()
//     {
//         $this->setExpectedException('\\Redis\\Exception\\RoleError', 'Only a node with role master may be returned (maybe the master was stepping down during connection?)');

//         $sentinel1 = $this->mockOnlineSentinelWithMasterSteppingDown();
//         $sentinel2 = $this->mockOnlineSentinel();
//         $sentinelSet = new SentinelSet('online-sentinel');
//         $sentinelSet->addSentinel($sentinel1);
//         $sentinelSet->addSentinel($sentinel2);
//         $sentinelSet->getMaster();
//     }

//     public function testThatABackoffIsAttempted()
//     {
//         $backoffOnce = new Incremental(0, 1);
//         $backoffOnce->setMaxAttempts(2);

//         $sentinel1 = $this->mockOfflineSentinel();
//         $sentinel2 = $this->mockOnlineSentinelWithMasterSteppingDown();

//         $sentinelSet = new SentinelSet('online-sentinel');
//         $sentinelSet->setBackoffStrategy($backoffOnce);
//         $sentinelSet->addSentinel($sentinel1);
//         $sentinelSet->addSentinel($sentinel2);
//         $masterNode = $sentinelSet->getMaster();

//         $this->assertEquals($this->onlineMasterHost, $masterNode->getHost(), 'A master that stepped down between discovery and connecting should be retried after backoff (check IP address)');
//         $this->assertEquals($this->onlineMasterPort, $masterNode->getPort(), 'A master that stepped down between discovery and connecting should be retried after backoff (check port)');
//     }

//     public function testThatTheMasterHasTheCorrectRole()
//     {
//         $noBackoff = new Incremental(0, 1);
//         $noBackoff->setMaxAttempts(1);

//         $sentinel1 = $this->mockOfflineSentinel();
//         $sentinel2 = $this->mockOnlineSentinel();

//         $sentinelSet = new SentinelSet('online-sentinel');
//         $sentinelSet->setBackoffStrategy($noBackoff);
//         $sentinelSet->addSentinel($sentinel1);
//         $sentinelSet->addSentinel($sentinel2);
//         $masterNode = $sentinelSet->getMaster();

//         $this->assertEquals(Client::ROLE_MASTER, $masterNode->getRole(), 'The role of the master should be \'master\'');
//     }
}