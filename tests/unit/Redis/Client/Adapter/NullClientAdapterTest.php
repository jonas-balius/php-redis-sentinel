<?php

namespace Redis\Client\Adapter;

use Redis\Client;

class NullClientAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testThatANullClientAlwaysLooksDisconnected()
    {
        $clientAdapter = new NullClientAdapter();
        $clientAdapter->connect();

        $this->assertEquals(true, $clientAdapter->isConnected(), 'Connected flag on null adapter is updated after connecting');
    }

    public function testThatTheMasterReturnedIsCorrectType()
    {
        $clientAdapter = new NullClientAdapter();
        $master = $clientAdapter->getMaster('test');
        $this->assertTrue(is_array($master), 'The master returned should be an array');
        $this->assertEquals(array('127.0.0.1', 6380), $master, 'The master returned does ot match expected');
    }

    public function testThatTheRoleIsAlwaysMaster()
    {
        $clientAdapter = new NullClientAdapter();
        $this->assertEquals(Client::ROLE_MASTER, $clientAdapter->getRole(), 'The role should be master');
    }
} 