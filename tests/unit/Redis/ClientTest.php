<?php

namespace Redis;

use Redis\Exception\ConnectionError;
use Redis\Client\Adapter\NullClientAdapter;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testClientHasHostAndPort()
    {
        $client = new Client('10.13.12.1', 8541);
        $this->assertEquals('10.13.12.1', $client->getHost(), 'Unable to get client host');
        $this->assertEquals(8541, $client->getPort(), 'Unable to get client port');
    }

    public function testClientHasPhpRedisAdapter()
    {
        $client = new Client('10.10.1.1', 1235, new Client\Adapter\PhpRedisClientAdapter());
        $this->assertInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', $client->getClientAdapter(), 'Can not retrevie correct client adapter');
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\PhpRedisClientAdapter', 'clientAdapter', $client, 'Not correct correct client adapter');
    }

    public function testClientAcceptsOtherAdapters()
    {
        $client = new Client('11.10.1.2', 2415, new NullClientAdapter());
        $this->assertInstanceOf('\\Redis\\Client\\Adapter\\NullClientAdapter', $client->getClientAdapter(), 'Can not swap client adapters');
        $this->assertAttributeInstanceOf('\\Redis\\Client\\Adapter\\NullClientAdapter', 'clientAdapter', $client, 'Can not swap client adapters');
    }

    public function testThatBeforeConnectingClientNodesKnowTheirConnectionState()
    {
        $client = new Client('12.2.3.2', 4444, $this->createClientAdapterMock('master', false));
        $this->assertFalse($client->isConnected(), 'A new client should not be connected');
    }

    public function testThatAfterAFailedConnectionAttemptClientNodesKnowTheirConnectionState()
    {
        $client = new Client('127.0.0.1', 4545, $this->createClientAdapterMock('master', false));
        try {
            $client->connect();
        } catch (ConnectionError $e) {

        }
        $this->assertFalse($client->isConnected(), 'After a failed connection attempt, the connection state should be false');
    }
    
    public function testThatExceptionIsThrownIfCanNotConnect()
    {
        $adapter = $this->createClientAdapterMock('master', false);
        $adapter->expects($this->any())
            ->method('connect')
            ->will($this->throwException(new \Exception('Can not connect')));
        
        $client = new Client('12.2.3.2', 4444, $adapter);
        
        $this->setExpectedException('\\Redis\\Exception\\ConnectionError', 'Unable to connect to redis at 12.2.3.2:4444');
        $client->connect();
    }

    public function testThatAfterASuccessfullConnectionTheClientsKnowsTheirConnectionState()
    {
        $client = new Client('142.2.2.2', 4547, $this->createClientAdapterMock('master', true));
        $client->connect();
        $this->assertTrue($client->isConnected(), 'After a successfull connection attempt, the connection state is bool(true)');
    }

    public function testThatWeCanGetTheClientAdapter()
    {
        $onlineClientAdapter = $this->createClientAdapterMock('master', false);
        $client = new Client('1.2.3.4', 7878, $onlineClientAdapter);
        $this->assertEquals($onlineClientAdapter, $client->getClientAdapter(), 'A client can not return the client adapter');
    }

    public function testThatTheRoleReturnedComesFromClientAdapter()
    {
        $client = new Client('10.3.6.9', 7451, $this->createClientAdapterMock('master', false));
        $this->assertEquals(Client::ROLE_MASTER, $client->getRole(), 'The role should be master');
        
        $client = new Client('10.2.5.9', 4124, $this->createClientAdapterMock('slave', false));
        $this->assertEquals(Client::ROLE_SLAVE, $client->getRole(), 'The role should be slave');
    }

    public function testThatNodeIsBeingIdentifiedCorrectly()
    {
        $client = new Client('10.1.9.8', 5123, $this->createClientAdapterMock('master', false));
        $this->assertTrue($client->isMaster(), 'A master should be identified as master');
        $this->assertFalse($client->isSlave(), 'A master should not be identified as slave');
        
        $client = new Client('10.2.3.4', 9521, $this->createClientAdapterMock('slave', false));
        $this->assertFalse($client->isMaster(), 'A slave should be identified as slave');
        $this->assertTrue($client->isSlave(), 'A slave should not be identified as master');
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