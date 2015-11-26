<?php

namespace Redis;

use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\NullClientAdapter;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    private $host = '127.0.0.1';
    private $port = 2323;

    public function testClientHasHostAndPort()
    {
        $client = new Client($this->host, $this->port);
        $this->assertEquals($this->host, $client->getHost(), 'Unable to get client host');
        $this->assertEquals($this->port, $client->getPort(), 'Unable to get client port');
    }

    public function testClientHasPhpRedisAdapter()
    {
        $client = new Client($this->host, $this->port, new Client\Adapter\PhpRedisClientAdapter());
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', 'clientAdapter', $client, 'Can not retrevie correct client adapter');
    }

    public function testClientAcceptsOtherAdapters()
    {
        $client = new Client($this->host, $this->port, new NullClientAdapter());
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\NullClientAdapter', 'clientAdapter', $client, 'Can not swap client adapters');
    }

    public function testThatFailureToConnectToClientsThrowsAnError()
    {
        $this->setExpectedException('\\Redis\\Exception\ConnectionError', sprintf('Could not connect to client at %s:%d', $this->host, $this->port));

        $clientNode = new Client($this->host, $this->port, $this->mockOfflineClientAdapter());
        $clientNode->connect();
    }

    public function testThatBeforeConnectingClientNodesKnowTheirConnectionState()
    {
        $clientNode = new Client($this->host, $this->port, $this->mockOfflineClientAdapter());
        $this->assertFalse($clientNode->isConnected(), 'A new client code object is not connected');
    }

    public function testThatAfterAFailedConnectionAttemptClientNodesKnowTheirConnectionState()
    {
        $clientNode = new Client($this->host, $this->port, $this->mockOfflineClientAdapter());
        try {
            $clientNode->connect();
        } catch (ConnectionError $e) {

        }
        $this->assertFalse($clientNode->isConnected(), 'After a failed connection attempt, the connection state should be bool(false)');
    }

    public function testThatAfterASuccessfullConnectionTheClientsKnowsTheirConnectionState()
    {
        $clientNode = new Client($this->host, $this->port, $this->mockOnlineClientAdapter());
        $clientNode->connect();
        $this->assertTrue($clientNode->isConnected(), 'After a successfull connection attempt, the connection state is bool(true)');
    }

    public function testThatWeCanGetTheClientAdapter()
    {
        $onlineClientAdapter = $this->mockOnlineClientAdapter();
        $clientNode = new Client($this->host, $this->port, $onlineClientAdapter);
        $this->assertEquals($onlineClientAdapter, $clientNode->getClientAdapter(), 'A client can not return the client adapter');
    }

    public function testThatTheRoleReturnedComesFromClientAdapter()
    {
        $masterClientAdapter = $this->mockClientAdapterForMaster();
        $masterNode = new Client($this->host, $this->port, $masterClientAdapter);
        $this->assertEquals(Client::ROLE_MASTER, $masterNode->getRole(), 'The role of the node is provided by the client adapter');
    }

    public function testThatAMasterIsBeingIdentifiedAsOne()
    {
        $masterClientAdapter = $this->mockClientAdapterForMaster();
        $masterNode = new Client($this->host, $this->port, $masterClientAdapter);
        $this->assertTrue($masterNode->isMaster(), 'A master should be identified as master');
        $this->assertFalse($masterNode->isSlave(), 'A master should not be identified as slave');
    }

    public function testThatASlaveIsBeingIdentifiedAsOne()
    {
        $slaveClientAdapter = $this->mockClientAdapterForSlave();
        $slaveNode = new Client($this->host, $this->port, $slaveClientAdapter);
        $this->assertTrue($slaveNode->isSlave(), 'A slave should be identified as slave');
        $this->assertFalse($slaveNode->isMaster(), 'A slave should not be identified as master');
    }
    
    private function mockOfflineClientAdapter()
    {
        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter');
        \Phake::when($redisClientAdapter)->connect()->thenThrow(
            new ConnectionError(sprintf('Could not connect to client at %s:%d', $this->host, $this->port))
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
    
    private function mockClientAdapterForMaster($masterClient = null)
    {
        return $this->mockClientAdapterForRole(Client::ROLE_MASTER, $masterClient);
    }
    
    private function mockClientAdapterForSlave($slaveClient = null)
    {
        return $this->mockClientAdapterForRole(Client::ROLE_SLAVE, $slaveClient);
    }
    
    private function mockClientAdapterForRole($role, $client = null)
    {
        if (empty($client)) {
            $client = \Phake::mock('\\Redis\\Client');
        }

        $redisClientAdapter = \Phake::mock('\\Redis\\Client\\Adapter\\SocketClientAdapter');
        \Phake::when($redisClientAdapter)->isConnected()->thenReturn(true);
        \Phake::when($redisClientAdapter)->getMaster('test')->thenReturn($client);
        \Phake::when($redisClientAdapter)->getRole()->thenReturn($role);
    
        return $redisClientAdapter;
    }
}