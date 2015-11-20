<?php

namespace Redis\Client\Adapter\Predis\Command;

use Predis\Command\Command as AbstractCommand;

class SentinelCommand extends AbstractCommand{
    
    const GETMASTER = 'get-master-addr-by-name';
    const GETSLAVES = 'slaves';

    public function getId(){
        return 'SENTINEL';
    }
} 