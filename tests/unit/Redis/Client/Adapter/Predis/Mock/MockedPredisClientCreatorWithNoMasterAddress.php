<?php

namespace Redis\Client\Adapter\Predis\Mock;

use Redis\Client\Adapter\Predis\ClientFactory;

class MockedPredisClientCreatorWithNoMasterAddress extends AbstractMockedPredisClientCreator implements ClientFactory
{
    public function createClient(array $parameters = array())
    {
        $mockedClient = \Phake::mock('\\Predis\\Client');
        \Phake::when($mockedClient)->isMaster()->thenReturn(false);
        \Phake::when($mockedClient)->getMaster(\Phake::anyParameters())->thenReturn($mockedClient);
        return $mockedClient;
    }
} 