<?php

namespace Redis;

require_once __DIR__ . '/Client/Adapter/Predis/Mock/MockedPredisClientCreatorWithNoMasterAddress.php';

use Redis\Client\Adapter\Predis\Mock\MockedPredisClientCreatorWithNoMasterAddress;
use Redis\Client\BackoffStrategy\Incremental;
use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\PredisClientAdapter;

class SentinelSetTest extends \PHPUnit_Framework_TestCase
{
    private $sentinelSetName = 'name-of-monitor-set';

    private $onlineSentinelHost = '127.0.0.1';
    private $onlineSentinelPort = 2424;

    private $onlineMasterHost = '198.100.10.1';
    private $onlineMasterPort = 5050;

    private $onlineSteppingDownMasterHost = '198.100.10.1';
    private $onlineSteppingDownMasterPort = 5050;

    private $offlineSentinelHost = '127.0.0.1';
    private $offlineSentinelPort = 2323;

    /**
     * @return \Redis\Client
     */
    private function mockOnlineSentinel()
    {
        $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithNoMasterAddress(), Client::TYPE_SENTINEL);

        $redisClient = \Phake::mock('\\Redis\Client');
        \Phake::when($redisClient)->getHost()->thenReturn($this->onlineMasterHost);
        \Phake::when($redisClient)->getPort()->thenReturn($this->onlineMasterPort);
        \Phake::when($redisClient)->isMaster()->thenReturn(true);
        \Phake::when($redisClient)->getRole()->thenReturn(Client::ROLE_MASTER);

        $sentinelClient = \Phake::mock('\\Redis\\Client');
        \Phake::when($sentinelClient)->connect()->thenReturn(null);
        \Phake::when($sentinelClient)->getHost()->thenReturn($this->onlineSentinelHost);
        \Phake::when($sentinelClient)->getPort()->thenReturn($this->onlineSentinelPort);
        \Phake::when($sentinelClient)->getClientAdapter()->thenReturn($clientAdapter);
        \Phake::when($sentinelClient)->getMaster(\Phake::anyParameters())->thenReturn($redisClient);

        return $sentinelClient;
    }

    /**
     * @return \Redis\Client
     */
    private function mockOfflineSentinel()
    {
        $sentinelClient = \Phake::mock('\\Redis\\Client');
        \Phake::when($sentinelClient)->connect()->thenThrow(
            new ConnectionError(sprintf('Could not connect to sentinel at %s:%d', $this->offlineSentinelHost, $this->offlineSentinelPort))
        );
        \Phake::when($sentinelClient)->getHost()->thenReturn($this->offlineSentinelHost);
        \Phake::when($sentinelClient)->getPort()->thenReturn($this->offlineSentinelPort);

        return $sentinelClient;
    }

    private function mockOnlineSentinelWithMasterSteppingDown()
    {
        $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithNoMasterAddress(), Client::TYPE_SENTINEL);

        $masterNodeSteppingDown = \Phake::mock('\\Redis\Client');
        \Phake::when($masterNodeSteppingDown)->getHost()->thenReturn($this->onlineSteppingDownMasterHost);
        \Phake::when($masterNodeSteppingDown)->getPort()->thenReturn($this->onlineSteppingDownMasterPort);
        \Phake::when($masterNodeSteppingDown)->isMaster()->thenReturn(false);

        $masterNode = \Phake::mock('\\Redis\Client');
        \Phake::when($masterNode)->getHost()->thenReturn($this->onlineMasterHost);
        \Phake::when($masterNode)->getPort()->thenReturn($this->onlineMasterPort);
        \Phake::when($masterNode)->isMaster()->thenReturn(true);

        $sentinelClient = \Phake::mock('\\Redis\\Client');
        \Phake::when($sentinelClient)->connect()->thenReturn(null);
        \Phake::when($sentinelClient)->getHost()->thenReturn($this->onlineSentinelHost);
        \Phake::when($sentinelClient)->getPort()->thenReturn($this->onlineSentinelPort);
        \Phake::when($sentinelClient)->getClientAdapter()->thenReturn($clientAdapter);
        \Phake::when($sentinelClient)->getMaster(\Phake::anyParameters())
            ->thenReturn($masterNodeSteppingDown)
            ->thenReturn($masterNode);

        return $sentinelClient;
    }

    public function testASentinelSetHasAName()
    {
        $sentinelSet = new SentinelSet($this->sentinelSetName);
        $this->assertEquals($this->sentinelSetName, $sentinelSet->getName(), 'A monitor set is identified by a name');
    }

    public function testASentinelSetNameCannotBeEmpty()
    {
        $this->setExpectedException('\\Redis\\Exception\\InvalidProperty', 'A monitor set needs a valid name');
        new SentinelSet('');
    }

    public function testThatSentinelClientsCanBeAddedToSentinelSets()
    {
        $sentinelSet = new SentinelSet($this->sentinelSetName);
        $sentinelSet->addSentinel($this->mockOnlineSentinel());
        $this->assertAttributeCount(1, 'sentinels', $sentinelSet, 'Sentinel node can be added to a monitor set');
    }

    public function testThatOnlySentinelClientObjectsCanBeAddedAsNode()
    {
        $this->setExpectedException('\\PHPUnit_Framework_Error', 'Argument 1 passed to Redis\SentinelSet::addSentinel() must be an instance of Redis\Client');
        $sentinelSet = new SentinelSet($this->sentinelSetName);
        $sentinelSet->addSentinel(new \StdClass());
    }

    public function testThatWeNeedNodesConfigurationToDiscoverAMaster()
    {
        $this->setExpectedException('\\Redis\\Exception\\ConfigurationError', 'You need to configure and add sentinel nodes before attempting to fetch a master');
        $sentinelSet = new SentinelSet($this->sentinelSetName);
        $sentinelSet->getMaster();
    }

    public function testThatMasterCannotBeFoundIfWeCannotConnectToSentinels()
    {
        $this->setExpectedException('\\Redis\\Exception\\ConnectionError', 'All sentinels are unreachable');
        $sentinel1 = $this->mockOfflineSentinel();
        $sentinel2 = $this->mockOfflineSentinel();
        $sentinelSet = new SentinelSet('all-fail');
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
        $sentinelSet->getMaster();
    }

    public function testThatSentinelNodeIsReturnedOnSuccessfulMasterDiscovery()
    {
        $noBackoff = new Incremental(0, 1);
        $noBackoff->setMaxAttempts(1);

        $sentinel1 = $this->mockOfflineSentinel();
        $sentinel2 = $this->mockOnlineSentinel();

        $sentinelSet = new SentinelSet('online-sentinel');
        $sentinelSet->setBackoffStrategy($noBackoff);
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
        $masterNode = $sentinelSet->getMaster();

        $this->assertInstanceOf('\\Redis\\Client', $masterNode, 'The master returned should be an instance of \\Redis\\Client');
        $this->assertEquals($this->onlineMasterHost, $masterNode->getHost(), 'The master node IP address returned should be the one of the online sentinel');
        $this->assertEquals($this->onlineMasterPort, $masterNode->getPort(), 'The master node IP port returned should be the one of the online sentinel');
    }

    public function testThatMasterStatusOfANodeIsCheckedAfterConnecting()
    {
        $this->setExpectedException('\\Redis\\Exception\\RoleError', 'Only a node with role master may be returned (maybe the master was stepping down during connection?)');

        $sentinel1 = $this->mockOnlineSentinelWithMasterSteppingDown();
        $sentinel2 = $this->mockOnlineSentinel();
        $sentinelSet = new SentinelSet('online-sentinel');
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
        $sentinelSet->getMaster();
    }

    public function testThatABackoffIsAttempted()
    {
        $backoffOnce = new Incremental(0, 1);
        $backoffOnce->setMaxAttempts(2);

        $sentinel1 = $this->mockOfflineSentinel();
        $sentinel2 = $this->mockOnlineSentinelWithMasterSteppingDown();

        $sentinelSet = new SentinelSet('online-sentinel');
        $sentinelSet->setBackoffStrategy($backoffOnce);
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
        $masterNode = $sentinelSet->getMaster();

        $this->assertEquals($this->onlineMasterHost, $masterNode->getHost(), 'A master that stepped down between discovery and connecting should be retried after backoff (check IP address)');
        $this->assertEquals($this->onlineMasterPort, $masterNode->getPort(), 'A master that stepped down between discovery and connecting should be retried after backoff (check port)');
    }

    public function testThatTheMasterHasTheCorrectRole()
    {
        $noBackoff = new Incremental(0, 1);
        $noBackoff->setMaxAttempts(1);

        $sentinel1 = $this->mockOfflineSentinel();
        $sentinel2 = $this->mockOnlineSentinel();

        $sentinelSet = new SentinelSet('online-sentinel');
        $sentinelSet->setBackoffStrategy($noBackoff);
        $sentinelSet->addSentinel($sentinel1);
        $sentinelSet->addSentinel($sentinel2);
        $masterNode = $sentinelSet->getMaster();

        $this->assertEquals(Client::ROLE_MASTER, $masterNode->getRole(), 'The role of the master should be \'master\'');
    }
}
 