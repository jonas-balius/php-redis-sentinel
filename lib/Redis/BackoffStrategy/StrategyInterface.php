<?php

namespace Redis\BackoffStrategy;

interface StrategyInterface{

    public function getBackoffInMicroSeconds();
    public function reset();
    public function shouldWeTryAgain();
} 