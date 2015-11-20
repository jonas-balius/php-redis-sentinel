<?php

namespace Redis\ClientSentinel;

use Redis\AbstractClientAdapter;

interface ClientSentinelAdapter extends AbstractClientAdapter{

    /**
     * Gets master
     * @param string $name - master name
     */
    public function getMaster($name);
} 