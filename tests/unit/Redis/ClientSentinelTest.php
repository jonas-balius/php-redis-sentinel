<?php

namespace Redis;

use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\SocketClientAdapter;

class ClientSentinelTest extends \PHPUnit_Framework_TestCase
{
    public function testClientHasHostAndPort()
    {
        $client = new ClientSentinel('10.13.12.1', 8541);
        $this->assertEquals('10.13.12.1', $client->getHost(), 'Unable to get client sentinel host');
        $this->assertEquals(8541, $client->getPort(), 'Unable to get client sentinel port');
    }
    
    public function testClientHasPhpRedisAdapter()
    {
        $client = new ClientSentinel('10.10.1.1', 1235, new Client\Adapter\PhpRedisClientAdapter());
        $this->assertInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', $client->getClientAdapter(), 'Can not retrevie correct client adapter');
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', 'clientAdapter', $client, 'Not correct correct client adapter');
    }
    
    public function testClientAcceptsOtherAdapters()
    {
        $client = new ClientSentinel('11.10.1.2', 2415, new SocketClientAdapter());
        $this->assertInstanceOf('\\Redis\\Client\\Adapter\\SocketClientAdapter', $client->getClientAdapter(), 'Can not swap client adapters');
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\SocketClientAdapter', 'clientAdapter', $client, 'Can not swap client adapters');
    }
    
    public function testThatBeforeConnectingClientNodesKnowTheirConnectionState()
    {
        $client = new ClientSentinel('12.2.3.2', 4444, $this->createClientAdapterMock('sentinel', false));
        $this->assertFalse($client->isConnected(), 'A new client should not be connected');
    }
    
    public function testThatAfterAFailedConnectionAttemptClientNodesKnowTheirConnectionState()
    {
        $client = new ClientSentinel('127.0.0.1', 4545, $this->createClientAdapterMock('sentinel', false));
        try {
            $client->connect();
        } catch (ConnectionError $e) {
    
        }
        $this->assertFalse($client->isConnected(), 'After a failed connection attempt, the connection state should be false');
    }
    
    public function testThatAfterASuccessfullConnectionTheClientsKnowsTheirConnectionState()
    {
        $client = new ClientSentinel('142.2.2.2', 4547, $this->createClientAdapterMock('sentinel', true));
        $client->connect();
        $this->assertTrue($client->isConnected(), 'After a successfull connection attempt, the connection state is bool(true)');
    }
    
    public function testThatWeCanGetTheClientAdapter()
    {
        $onlineClientAdapter = $this->createClientAdapterMock('sentinel', false);
        $client = new ClientSentinel('1.2.3.4', 7878, $onlineClientAdapter);
        $this->assertEquals($onlineClientAdapter, $client->getClientAdapter(), 'A client can not return the client adapter');
    }
    
    public function testThatTheRoleReturnedComesFromClientAdapter()
    {
        $client = new ClientSentinel('10.3.6.9', 7451, $this->createClientAdapterMock('sentinel', false));
        $this->assertEquals(ClientSentinel::ROLE_SENTINEL, $client->getRole(), 'The role should be sentinel');
    }
    
    public function testThatNodeIsBeingIdentifiedCorrectly()
    {
        $client = new ClientSentinel('10.1.9.8', 5123, $this->createClientAdapterMock('sentinel', false));
        $this->assertTrue($client->isSentinel(), 'A sentinel should be identified as sentinel');
    }
    
    public function testThatTheMasterReturnedComesFromClientAdapter()
    {
        $adapter = $this->createClientAdapterMock('sentinel', false);
        $client = new ClientSentinel('1.2.3.4', 7878, $adapter);
        $this->assertEquals(array('154.21.25.1', 6379), $client->getMaster('test-master'), 'Can not get master');
        
        $this->setExpectedException('\\Redis\\Exception\\SentinelError', 'The sentinel does not know the master address');
        $this->assertEquals(array('154.21.25.1', 6379), $client->getMaster('invalid-master'), 'Should not be able to get non existent master');
    }
    
    /**
     * Creates client adapter mock
     * @param string $type (master|slave|sentinel)
     * @param bool $connected - connected or not
     * @return PhpRedisClientAdapter
     */
    private function createClientAdapterMock($type, $connected = true){
    
        switch ($type) {
            case 'master':
                $client = $this->createMasterClientMock($connected);
                break;
            case 'slave':
                $client = $this->createSlaveClientMock($connected);
                break;
            case 'sentinel':
            default:
                $client = $this->createSentinelClientMock($connected);
                break;
        }
    
        $adapter = $this->getMockBuilder('\Redis\Client\Adapter\PhpRedisClientAdapter')
            ->setMethods(array('getClient', 'isConnected', 'connect'))
            ->getMock();
    
        $adapter->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($client));
    
        $adapter->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue($connected));
    
        $adapter->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($client->connect()));
    
        return $adapter;
    }
    
    /**
     * Creates master client mock
     * @param bool $connected - connected or not
     * @return \Redis
     */
    private function createMasterClientMock($connected = false)
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('info', 'connect'))
            ->getMock();
    
        $client->expects($this->any())
            ->method('info')
            ->will($this->returnValue(array('role' => 'master')));
    
        $client->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($connected));
    
        return $client;
    }
    
    /**
     * Creates slave client mock
     * @param bool $connected - connected or not
     * @return \Redis
     */
    private function createSlaveClientMock($connected = false)
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('info', 'connect'))
            ->getMock();
    
        $client->expects($this->any())
            ->method('info')
            ->will($this->returnValue(array('role' => 'slave')));
    
        $client->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($connected));
    
        return $client;
    }
    
    /**
     * Creates sentinel client mock
     * @param bool $connected - connected or not
     * @return \Redis
     */
    private function createSentinelClientMock($connected = false)
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('info', 'connect'))
            ->getMock();
    
        $client->expects($this->any())
            ->method('info')
            ->will($this->returnValue(array('role' => 'sentinel',
                                            'master0' => 'name=test-master,status=ok,address=154.21.25.1:6379,slaves=2,sentinels=3')));
    
        $client->expects($this->any())
            ->method('connect')
            ->will($this->returnValue($connected));
    
        return $client;
    }
} 