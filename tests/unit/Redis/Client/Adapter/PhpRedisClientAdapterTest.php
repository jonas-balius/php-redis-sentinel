<?php

namespace Redis\Client\Adapter;

use Redis\Client;

class PhpRedisClientAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        //$this->markTestSkipped('We do not test PhpRedis.');
    }
    
    public function testThatPhpRredisClientIsCreatedOnConnect()
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('phpredis extension is not loaded so we skip this test');
        }
        
        $clientAdapter = $this->createClientAdapterMock('master');
        $clientAdapter->setHost('127.0.0.1');
        $clientAdapter->setPort(4545);
        $clientAdapter->connect();

        $this->assertInstanceOf('\\Redis', $clientAdapter->getClient(), 'The adapter should return a \Redis object');
        $this->assertAttributeInstanceOf('\\Redis', 'client', $clientAdapter, 'The adapter should create and configure a \Redis object');
    }
    
    public function testThatMasterRoleIsCorrect()
    {
        $clientAdapter = $this->createClientAdapterMock('master');
        $clientAdapter->setHost('192.168.12.10');
        $clientAdapter->setPort(1020);
    
        $this->assertEquals(Client::ROLE_MASTER, $clientAdapter->getRole(), 'The master role should be "master"');
    }
    
    public function testThatSentinelRoleIsCorrect()
    {
        $clientAdapter = $this->createClientAdapterMock('sentinel');
        $clientAdapter->setHost('142.21.21.1');
        $clientAdapter->setPort(2030);
    
        $this->assertEquals(Client::ROLE_SENTINEL, $clientAdapter->getRole(), 'The master role should be "master"');
    }

    public function testThatMasterIsOfCorrectType()
    {
        $clientAdapter = $this->createClientAdapterMock('sentinel');
        $clientAdapter->setHost('124.2.21.51');
        $clientAdapter->setPort(4545);
        
        $this->assertEquals(array('154.21.25.1', 6379), $clientAdapter->getMaster('mymaster'), 'Wrong master address');
    }
    
    public function testThatExceptionIsThrownWhenMasterIsUnknownToSentinel()
    {
        $clientAdapter = $this->createClientAdapterMock('sentinel');
        $clientAdapter->setHost('124.2.21.2');
        $clientAdapter->setPort(4548);
        
        $this->setExpectedException('\\Redis\\Exception\\SentinelError', 'The sentinel does not know the master address');
        $this->assertEquals(array('127.0.0.1', 6381), $clientAdapter->getMaster('testmaster'), 'This master should not exists');
    }

    /**
     * Creates client adapter mock
     * @param string $type (master|slave|sentinel)
     * @return PhpRedisClientAdapter
     */
    private function createClientAdapterMock($type){
        
        switch ($type) {
            case 'master':
                $client = $this->createMasterClientMock();
            break;
            case 'slave':
                $client = $this->createSlaveClientMock();
            break;
            case 'sentinel':
            default:
                $client = $this->createSentinelClientMock();
            break;
        }
        
        $clientAdapter = $this->getMockBuilder('\Redis\Client\Adapter\PhpRedisClientAdapter')
            ->setMethods(array('getClient'))
            ->getMock();
        
        $clientAdapter->expects($this->any())
            ->method('getClient')
            ->will($this->returnValue($client));
        
        return $clientAdapter;
    }
    
    /**
     * Creates master client mock
     * @return \Redis
     */
    private function createMasterClientMock()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('info'))
            ->getMock();
    
        $client->expects($this->any())
            ->method('info')
            ->will($this->returnValue(array('role' => 'master')));
    
        return $client;
    }
    
    /**
     * Creates slave client mock
     * @return \Redis
     */
    private function createSlaveClientMock()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('info'))
            ->getMock();
    
        $client->expects($this->any())
            ->method('info')
            ->will($this->returnValue(array('role' => 'slave')));
    
        return $client;
    }
    
    /**
     * Creates sentinel client mock
     * @return \Redis
     */
    private function createSentinelClientMock()
    {
        $client = $this->getMockBuilder('\Redis')
            ->setMethods(array('info'))
            ->getMock();
    
        $client->expects($this->any())
            ->method('info')
            ->will($this->returnValue(array('role' => 'sentinel',
                                            'master0' => 'name=mymaster,status=ok,address=154.21.25.1:6379,slaves=2,sentinels=3')));
    
        return $client;
    }
}