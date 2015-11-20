<?php

namespace Redis\ClientSentinel;

use Redis\AbstractClientAdapter;

interface ClientSentinelAdapter extends AbstractClientAdapter{

    /**
     * Gets master
     * @param string $name - master name
     * @return array - first element is host and second is port
     */
    public function getMaster($name);
} 