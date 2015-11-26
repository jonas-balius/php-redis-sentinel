<?php

namespace Redis\BackoffStrategy;

use Redis\BackoffStrategy\StrategyInterface;

class None implements StrategyInterface{

    private $incrementalStrategy;

    public function __construct(){
        $this->incrementalStrategy = new Incremental(0, 0);
        $this->incrementalStrategy->setMaxAttempts(0);
    }

    public function reset(){
        $this->incrementalStrategy->reset();
    }

    public function getBackoffInMicroSeconds(){
        return $this->incrementalStrategy->getBackoffInMicroSeconds();
    }

    public function shouldWeTryAgain(){
        return $this->incrementalStrategy->shouldWeTryAgain();
    }
} 