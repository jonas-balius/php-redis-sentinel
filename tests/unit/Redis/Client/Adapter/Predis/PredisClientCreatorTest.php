<?php


namespace Redis\Client\Adapter\Predis;


use Redis\Client;

class PredisClientCreatorTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('We do not test Predis.');
    }
    
    public function testThatSentinelClientIsCorrectType()
    {
        $clientFactory = new PredisClientCreator();

        $this->assertInstanceOf(
            '\\Predis\\Client',
            $clientFactory->createClient(Client::TYPE_SENTINEL, array()),
            'Verify that sentinel clients created are of type \\Redis\\Client'
        );
    }

    public function testThatSentinelClientsSupportSentinelCommand()
    {
        $clientFactory = new PredisClientCreator();
        $sentinelClient = $clientFactory->createClient(Client::TYPE_SENTINEL);
        $this->assertTrue(
            $sentinelClient->getProfile()->supportsCommand('sentinel'),
            'Verify that sentinel clients can execute the SENTINEL command'
        );
    }

    public function testThatSentinelClientsSupportRoleCommand()
    {
        $clientFactory = new PredisClientCreator();
        $sentinelClient = $clientFactory->createClient(Client::TYPE_SENTINEL);
        $this->assertTrue(
            $sentinelClient->getProfile()->supportsCommand('role'),
            'Verify that sentinel clients can execute the ROLE command'
        );
    }

    public function testThatRedisClientIsCorrectType()
    {
        $clientFactory = new PredisClientCreator();

        $this->assertInstanceOf(
            '\\Predis\\Client',
            $clientFactory->createClient(Client::TYPE_REDIS),
            'Verify that redis clients created are of type \\Redis\\Client'
        );
    }

    public function testThatRedisClientsSupportRoleCommand()
    {
        $clientFactory = new PredisClientCreator();
        $redisClient = $clientFactory->createClient(Client::TYPE_REDIS);
        $this->assertTrue(
            $redisClient->getProfile()->supportsCommand('role'),
            'Verify that redis clients support the ROLE command'
        );
    }

    public function testThatAnExceptionIsThrownForInvalidClientTypes()
    {
        $this->setExpectedException('\\Redis\\Exception\\ConfigurationError', 'To create a client, you need to provide a valid client type');

        $clientFactory = new PredisClientCreator();
        $clientFactory->createClient('boe');
    }
}
 