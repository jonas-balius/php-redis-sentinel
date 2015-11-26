<?php

namespace Redis;

use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\NullClientAdapter;

class ClientSentinelTest extends \PHPUnit_Framework_TestCase
{
    private $host = '127.0.0.1';
    private $port = 2323;

    public function testSentinelHasHostAndPort()
    {
        $sentinel = new ClientSentinel($this->host, $this->port);
        $this->assertEquals($this->host, $sentinel->getHost(), 'Unable to get sentinel host');
        $this->assertEquals($this->port, $sentinel->getPort(), 'Unable to get sentinel port');
    }

    public function testSentinelHasPhpRedisAdapter()
    {
        $sentinel = new ClientSentinel($this->host, $this->port, new Client\Adapter\PhpRedisClientAdapter());
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', 'clientAdapter', $sentinel, 'Can not retrevie correct adapter');
    }

    public function testSentinelAcceptsOtherAdapters()
    {
        $sentinel = new ClientSentinel($this->host, $this->port, new NullClientAdapter());
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\NullClientAdapter', 'clientAdapter', $sentinel, 'The used redis client adapter can be swapped');
    }

    public function testThatFailureToConnectToSentinelsThrowsAnError()
    {
        $this->setExpectedException('\\Redis\\Exception\ConnectionError', sprintf('Could not connect to sentinel at %s:%d', $this->host, $this->port));

        $sentinelNode = new ClientSentinel($this->host, $this->port, $this->mockOfflineClientAdapter());
        $sentinelNode->connect();
    }

    public function testThatBeforeConnectingSentinelNodesKnowTheirConnectionState()
    {
        $sentinelNode = new ClientSentinel($this->host, $this->port, $this->mockOfflineClientAdapter());
        $this->assertFalse($sentinelNode->isConnected(), 'A new sentinel code object is not connected');
    }

    public function testThatAfterAFailedConnectionAttemptSentinelNodesKnowTheirConnectionState()
    {
        $sentinelNode = new ClientSentinel($this->host, $this->port, $this->mockOfflineClientAdapter());
        try {
            $sentinelNode->connect();
        } catch (ConnectionError $e) {

        }
        $this->assertFalse($sentinelNode->isConnected(), 'After a failed connection attempt, the connection state should be bool(false)');
    }

    public function testThatAfterASuccessfullConnectionTheSentinelsKnowsTheirConnectionState()
    {
        $sentinelNode = new ClientSentinel($this->host, $this->port, $this->mockOnlineClientAdapter());
        $sentinelNode->connect();
        $this->assertTrue($sentinelNode->isConnected(), 'After a successfull connection attempt, the connection state is bool(true)');
    }

    public function testThatWeCanGetTheClientAdapter()
    {
        $onlineClientAdapter = $this->mockOnlineClientAdapter();
        $sentinelNode = new ClientSentinel($this->host, $this->port, $onlineClientAdapter);
        $this->assertEquals($onlineClientAdapter, $sentinelNode->getClientAdapter(), 'A sentinel can return the client adapter');
    }

    public function testThatTheMasterReturnedComesFromClientAdapter()
    {
        $masterClient = \Phake::mock('\\Redis\\Client');
        $masterClientAdapter = $this->mockClientAdapterForSentinel($masterClient);
        $masterNode = new ClientSentinel($this->host, $this->port, $masterClientAdapter);
        $this->assertEquals($masterClient, $masterNode->getMaster('test'), 'The redis client gets the master object from the client adapter');
    }

    public function testThatTheRoleReturnedComesFromClientAdapter()
    {
        $sentinelClientAdapter = $this->mockClientAdapterForSentinel();
        $sentinelNode = new ClientSentinel($this->host, $this->port, $sentinelClientAdapter);
        $this->assertEquals(Client::ROLE_SENTINEL, $sentinelNode->getRole(), 'The role of the node is provided by the client adapter');
    }

    public function testThatASentinelIsBeingIdentifiedAsOne()
    {
        $sentinelClientAdapter = $this->mockClientAdapterForSentinel();
        $sentinelNode = new ClientSentinel($this->host, $this->port, $sentinelClientAdapter);
        $this->assertTrue($sentinelNode->isSentinel(), 'A sentinel should be identified as sentinel');
    }

    private function mockOfflineClientAdapter()
    {
        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter');
        \Phake::when($redisClientAdapter)->connect()->thenThrow(
            new ConnectionError(sprintf('Could not connect to sentinel at %s:%d', $this->host, $this->port))
        );
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(false);
    
        return $redisClientAdapter;
    }
    
    private function mockOnlineClientAdapter()
    {
        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter');
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(true);
    
        return $redisClientAdapter;
    }
    
    private function mockClientAdapterForSentinel($sentinelClient = null)
    {
        return $this->mockClientAdapterForRole(Client::ROLE_SENTINEL, $sentinelClient);
    }
    
    private function mockClientAdapterForRole($role, $client = null)
    {
        if (empty($client)) {
            $client = \Phake::mock('\\Redis\\ClientSentinel');
        }

        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\SocketClientAdapter');
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(true);
        \Phake::when($redisClientAdapter)->getMaster('test')->thenReturn($client);
        \Phake::when($redisClientAdapter)->getRole()->thenReturn($role);
    
        return $redisClientAdapter;
    }
} 