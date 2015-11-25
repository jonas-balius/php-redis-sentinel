<?php

namespace Redis;

use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\NullClientAdapter;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    private $host = '127.0.0.1';
    private $port = 2323;

    private function mockOfflineClientAdapter()
    {
        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\PredisClientAdapter');
        \Phake::when($redisClientAdapter)->connect()->thenThrow(
            new ConnectionError(sprintf('Could not connect to sentinel at %s:%d', $this->host, $this->port))
        );
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(false);

        return $redisClientAdapter;
    }

    private function mockOnlineClientAdapter()
    {
        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\PredisClientAdapter');
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(true);

        return $redisClientAdapter;
    }

    private function mockClientAdapterForMaster($masterClient = null)
    {
        return $this->mockClientAdapterForRole(Client::ROLE_MASTER, $masterClient);
    }

    private function mockClientAdapterForSlave($slaveClient = null)
    {
        return $this->mockClientAdapterForRole(Client::ROLE_SLAVE, $slaveClient);
    }

    private function mockClientAdapterForSentinel($sentinelClient = null)
    {
        return $this->mockClientAdapterForRole(Client::ROLE_SENTINEL, $sentinelClient);
    }

    private function mockClientAdapterForRole($role, $client = null)
    {
        if (empty($client)) {
            $client = \Phake::mock('\\Redis\\Client');
        }

        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\PredisClientAdapter');
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(true);
        \Phake::when($redisClientAdapter)->getMaster('test')->thenReturn($client);
        \Phake::when($redisClientAdapter)->getRole()->thenReturn(array($role));

        return $redisClientAdapter;
    }

    public function testSentinelHasHost()
    {
        $sentinel = new Client($this->host, $this->port);
        $this->assertEquals($this->host, $sentinel->getHost(), 'A sentinel location is identified by ip address');
    }

    public function testSentinelRequiresAValidHost()
    {
        $this->setExpectedException('\\Redis\\Exception\\InvalidProperty', 'A sentinel node requires a valid IP address');
        new Client('blabla', $this->port);
    }

    public function testSentinelHasPort()
    {
        $sentinel = new Client($this->host, $this->port);
        $this->assertEquals($this->port, $sentinel->getPort(), 'A sentinel location needs a port to be identifiable');
    }

    public function testSentinelHasPredisAsStandardAdapter()
    {
        $sentinel = new Client($this->host, $this->port);
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\PredisClientAdapter', 'clientAdapter', $sentinel, 'By default, the library uses predis to make connection with redis');
    }

    public function testSentinelAcceptsOtherAdapters()
    {
        $sentinel = new Client($this->host, $this->port, new NullClientAdapter());
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\NullClientAdapter', 'clientAdapter', $sentinel, 'The used redis client adapter can be swapped');
    }

    public function testSentinelRefusesTextAsAnInvalidPort()
    {
        $this->setExpectedException('\\Redis\\Exception\\InvalidProperty', 'A sentinel node requires a valid service port');
        new Client($this->host, 'abc');
    }

    public function testThatFailureToConnectToSentinelsThrowsAnError()
    {
        $this->setExpectedException('\\Redis\\Exception\ConnectionError', sprintf('Could not connect to sentinel at %s:%d', $this->host, $this->port));

        $sentinelNode = new Client($this->host, $this->port, $this->mockOfflineClientAdapter());
        $sentinelNode->connect();
    }

    public function testThatBeforeConnectingSentinelNodesKnowTheirConnectionState()
    {
        $sentinelNode = new Client($this->host, $this->port, $this->mockOfflineClientAdapter());
        $this->assertFalse($sentinelNode->isConnected(), 'A new sentinel code object is not connected');
    }

    public function testThatAfterAFailedConnectionAttemptSentinelNodesKnowTheirConnectionState()
    {
        $sentinelNode = new Client($this->host, $this->port, $this->mockOfflineClientAdapter());
        try {
            $sentinelNode->connect();
        } catch (ConnectionError $e) {

        }
        $this->assertFalse($sentinelNode->isConnected(), 'After a failed connection attempt, the connection state should be bool(false)');
    }

    public function testThatAfterASuccessfullConnectionTheSentinelsKnowsTheirConnectionState()
    {
        $sentinelNode = new Client($this->host, $this->port, $this->mockOnlineClientAdapter());
        $sentinelNode->connect();
        $this->assertTrue($sentinelNode->isConnected(), 'After a successfull connection attempt, the connection state is bool(true)');
    }

    public function testThatWeCanGetTheClientAdapter()
    {
        $onlineClientAdapter = $this->mockOnlineClientAdapter();
        $sentinelNode = new Client($this->host, $this->port, $onlineClientAdapter);
        $this->assertEquals($onlineClientAdapter, $sentinelNode->getClientAdapter(), 'A sentinel can return the client adapter');
    }

    public function testThatTheMasterReturnedComesFromClientAdapter()
    {
        $masterClient = \Phake::mock('\\Redis\\Client');
        $masterClientAdapter = $this->mockClientAdapterForMaster($masterClient);
        $masterNode = new Client($this->host, $this->port, $masterClientAdapter);
        $this->assertEquals($masterClient, $masterNode->getMaster('test'), 'The redis client gets the master object from the client adapter');
    }

    public function testThatTheRoleReturnedComesFromClientAdapter()
    {
        $masterClientAdapter = $this->mockClientAdapterForMaster();
        $masterNode = new Client($this->host, $this->port, $masterClientAdapter);
        $this->assertEquals(array(Client::ROLE_MASTER), $masterNode->getRole(), 'The role of the node is provided by the client adapter');
    }

    public function testThatTheRoleTypeReturnedComesFromClientAdapter()
    {
        $masterClientAdapter = $this->mockClientAdapterForMaster();
        $masterNode = new Client($this->host, $this->port, $masterClientAdapter);
        $this->assertEquals(Client::ROLE_MASTER, $masterNode->getRole(), 'The type of role of the node is provided by the client adapter');
    }

    public function testThatAMasterIsBeingIdentifiedAsOne()
    {
        $masterClientAdapter = $this->mockClientAdapterForMaster();
        $masterNode = new Client($this->host, $this->port, $masterClientAdapter);
        $this->assertTrue($masterNode->isMaster(), 'A master should be identified as master');
        $this->assertFalse($masterNode->isSlave(), 'A master should not be identified as slave');
        $this->assertFalse($masterNode->isSentinel(), 'A master should not be identified as sentinel');
    }

    public function testThatASentinelIsBeingIdentifiedAsOne()
    {
        $sentinelClientAdapter = $this->mockClientAdapterForSentinel();
        $sentinelNode = new Client($this->host, $this->port, $sentinelClientAdapter);
        $this->assertTrue($sentinelNode->isSentinel(), 'A sentinel should be identified as sentinel');
        $this->assertFalse($sentinelNode->isSlave(), 'A sentinel should not be identified as slave');
        $this->assertFalse($sentinelNode->isMaster(), 'A sentinel should not be identified as master');
    }

    public function testThatASlaveIsBeingIdentifiedAsOne()
    {
        $slaveClientAdapter = $this->mockClientAdapterForSlave();
        $slaveNode = new Client($this->host, $this->port, $slaveClientAdapter);
        $this->assertTrue($slaveNode->isSlave(), 'A slave should be identified as slave');
        $this->assertFalse($slaveNode->isSentinel(), 'A slave should not be identified as sentinel');
        $this->assertFalse($slaveNode->isMaster(), 'A slave should not be identified as master');
    }
}
 