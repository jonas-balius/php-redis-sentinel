<?php


namespace Redis;

class ConnectingTest extends Redis_Integration_TestCase
{
    protected function setUp(){
        
        if (!extension_loaded('redis')) {
            $this->markAsSkipped('phpredis extension is not loaded so we skip the tests');
        }
    }
    
    public function testThatWeCanConnectToSentinelsAndInspectTheirRole()
    {
        $clientAdapter = new Client\Adapter\SocketClientAdapter();
        
        $sentinels = array( new ClientSentinel('127.0.0.1', '26379', $clientAdapter),
                            new ClientSentinel('127.0.0.1', '26380', $clientAdapter),
                            new ClientSentinel('127.0.0.1', '26381', $clientAdapter)
        );
        
        foreach ($sentinels as $key => $sentinel) {
            $sentinel->connect();
            $this->assertTrue($sentinel->isConnected(), 'Can not connect to sentinel '. $key);
            $this->assertEquals(Client::ROLE_SENTINEL, $sentinel->getRole(), 'The role returned by sentinel '. $key. ' is wrong');
            $this->assertTrue($sentinel->isSentinel(), 'Sentinel '. $key. ' is not a sentinel');
        }
    }
    
    public function testThatWeCanConnectToMasterAndInspectTheRole()
    {
        $master = new Client('127.0.0.1', '6381', new Client\Adapter\PhpRedisClientAdapter());
        $this->assertEquals(Client::ROLE_MASTER, $master->getRole(), 'Incorrect role for master');
        $this->assertTrue($master->isMaster(), 'Can not verify that master is a master');
    }
    
    public function testThatWeCanConnectToSlaveAndInspectTheRole()
    {
        $slave = new Client('127.0.0.1', '6380', new Client\Adapter\PhpRedisClientAdapter());
        $this->assertEquals(Client::ROLE_SLAVE, $slave->getRole(), 'The slave should be identified with that type');
        $this->assertTrue($slave->isSlave(), 'Verify the slave is a slave');
        
        $slave = new Client('127.0.0.1', '6379', new Client\Adapter\PhpRedisClientAdapter());
        $this->assertEquals(Client::ROLE_SLAVE, $slave->getRole(), 'The slave should be identified with that type');
        $this->assertTrue($slave->isSlave(), 'Verify the slave is a slave');
    }
}
 