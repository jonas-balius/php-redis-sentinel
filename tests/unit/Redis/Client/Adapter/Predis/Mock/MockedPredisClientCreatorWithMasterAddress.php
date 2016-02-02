<?php

namespace Redis\Client\Adapter\Predis\Mock;

use Redis\Client\Adapter\Predis\ClientFactory;
use Redis\Client;

class MockedPredisClientCreatorWithMasterAddress extends AbstractMockedPredisClientCreator implements ClientFactory
{
    public function createClient(array $parameters = array())
    {
        throw new \Exception('createClient not implemented');
        // TODO: refactor to use PhpUnit
        $mockedClient = \Phake::mock('\\Predis\\Client');
        \Phake::when($mockedClient)->isMaster()->thenReturn(false);
        \Phake::when($mockedClient)->role()->thenReturn(array(Client::ROLE_SENTINEL));
        \Phake::when($mockedClient)->getMaster(\Phake::anyParameters())->thenReturn($mockedClient);
        return $mockedClient;
    }
}