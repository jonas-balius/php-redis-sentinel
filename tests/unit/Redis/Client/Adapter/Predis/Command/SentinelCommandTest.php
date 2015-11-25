<?php

namespace Redis\Client\Adapter\Predis\Command;


class SentinelCommandTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->markTestSkipped('We do not test Predis.');
    }
    
    public function testThatTheCorrectIdIsReturned()
    {
        $command = new \Redis\Client\Adapter\Predis\Command\SentinelCommand();
        $this->assertEquals('SENTINEL', $command->getId(), 'Test that the id of the command is correct (SENTINEL)');
    }
}
 