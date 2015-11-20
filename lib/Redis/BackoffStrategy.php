<?php

namespace Redis;

interface BackoffStrategy{

    public function getBackoffInMicroSeconds();
    public function reset();
    public function shouldWeTryAgain();
} 