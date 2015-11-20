<?php

namespace Redis\ClientSentinel\Adapter\Predis\Command;

use Predis\Command\Command as AbstractCommand;

class RoleCommand extends AbstractCommand{
    
    public function getId(){
        return 'ROLE';
    }
} 