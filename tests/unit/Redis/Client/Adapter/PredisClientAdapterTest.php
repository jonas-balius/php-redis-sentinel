<?php

namespace Redis\Client\Adapter;

use Redis\Client\Adapter\Predis\Mock\MockedPredisClientCreatorWithMasterAddress;
use Redis\Client\Adapter\Predis\Mock\MockedPredisClientCreatorWithNoMasterAddress;
use Redis\Client;

class PredisClientAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('We do not test Predis.');
    }
    
    public function testThatAPredisClientIsCreatedOnConnect()
    {
        $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithNoMasterAddress());
        $clientAdapter->setHost('127.0.0.1');
        $clientAdapter->setPort(4545);
        $clientAdapter->connect();

        $this->assertAttributeInstanceOf('\\Predis\\Client', 'client', $clientAdapter, 'The adapter should create and configure a \\Predis\\Client object');
    }

    public function testThatMasterIsOfCorrectType()
    {
        $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithMasterAddress());
        $clientAdapter->setHost('127.0.0.1');
        $clientAdapter->setPort(4545);
        $clientAdapter->connect();
        $master = $clientAdapter->getMaster('test');

        $this->assertInstanceOf('\\Redis\\Client', $master, 'The master returned should be of type \\Redis\\Client');
    }

    public function testThatExceptionIsThrownWhenMasterIsUnknownToSentinel()
    {
        $this->setExpectedException('\\Redis\\Exception\\SentinelError', 'The sentinel does not know the master address');

        $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithNoMasterAddress());
        $clientAdapter->setHost('127.0.0.1');
        $clientAdapter->setPort(4545);
        $clientAdapter->connect();
        $clientAdapter->getMaster('test');
    }

    public function testThatTheAdapterReturnsTheRoleOfTheServer()
    {
        $clientAdapter = new PredisClientAdapter(new MockedPredisClientCreatorWithMasterAddress());
        $clientAdapter->setHost('127.0.0.1');
        $clientAdapter->setPort(4545);
        $clientAdapter->connect();

        $this->assertEquals('sentinel', $clientAdapter->getRole(), 'The server we are connected to is a sentinel');
    }
}
 