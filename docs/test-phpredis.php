<?php


use Redis\ClientSentinel\Adapter\PredisClientAdapter;
use Redis\ClientSentinel\Adapter\SocketClientAdapter;
use Redis\ClientSentinel\Adapter\NullClientAdapter;
use Redis\ClientSentinel\Adapter\Predis\ClientCreator as SentinelCreator;
use Redis\Client\Adapter\Predis\ClientCreator as RedisCreator;
use Redis\ClientSentinel;
use Redis\SentinelSet;

use Redis\Client\Adapter\PredisClientAdapter as ClientAdapter;
use Redis\Client\Adapter\PhpRedisClientAdapter;

echo 0;

//$redisLibraryAdapter = new PredisClientAdapter(new SentinelCreator());
//$redisLibraryAdapter = new PredisClientAdapter();
//$redisLibraryAdapter = new NullClientAdapter();
$redisLibraryAdapter = new SocketClientAdapter();

echo 1;

$sentinel1 = new ClientSentinel('127.0.0.1', 26379, $redisLibraryAdapter);
$sentinel2 = new ClientSentinel('127.0.0.1', 26380, $redisLibraryAdapter);
$sentinel3 = new ClientSentinel('127.0.0.1', 26381, $redisLibraryAdapter);

echo 2;

$monitor = new SentinelSet('test-master', new PhpRedisClientAdapter());

echo 3;

$monitor->addSentinel($sentinel1);
$monitor->addSentinel($sentinel2);
$monitor->addSentinel($sentinel3);

echo 4;

try {
    $redis = $monitor->getMaster();
    
    echo 5;
    
    $redis->connect();
    
//     print_r($redis);
//     exit;
    
    $client = $redis->getClientAdapter()->getClient();
    
    $client->set('keyname', '-b-');
    echo ' '. $client->get('keyname');
} catch (\Exception $e ) {
    
    print_r($e);
    
    die('something wrong');
}



echo ' done            ';

var_dump($client);
exit;




error_reporting(-1);

require_once('../_includes/Composer/vendor/lancerhe/php-redis-sentinel/src/RedisSentinel/ConnectionFailureExecption.php');
require_once('../_includes/Composer/vendor/lancerhe/php-redis-sentinel/src/RedisSentinel/ConnectionTcpExecption.php');
require_once('../_includes/Composer/vendor/lancerhe/php-redis-sentinel/src/RedisSentinel/Client.php');
require_once('../_includes/Composer/vendor/lancerhe/php-redis-sentinel/src/RedisSentinel/Sentinel.php');


echo 6;
exit;

$master_name = 'my_master';
$sentinel = new \RedisSentinel\Sentinel($master_name);


$sentinel->add(new \RedisSentinel\Client('192.168.1.2', 26379));
$sentinel->add(new \RedisSentinel\Client('192.168.1.3', 26379));
$sentinel->add(new \RedisSentinel\Client('192.168.1.4', 26379));

var_dump( $sentinel->getMaster() );
var_dump( $sentinel->getSlaves() );
var_dump( $sentinel->getSlave() ); // Random, one of slaves.



exit;



$redis = new \Redis();
$redis->connect('127.0.0.1', 26380);
$redis->sentinel('get-master-addr-by-name test-master');

print_r($redis->info());

throw new \Exception('testas');


exit;


exit;
$redis->connect('127.0.0.1', 6379);
$redis->set('zzz', 'test-val-2');
$value = $redis->get('zzz');
     
var_dump($value);

//sleep(1);

$redis = new Redis();
$redis->connect('127.0.0.1', 6380);
//$redis->set('zzz', 'test2');
$value = $redis->get('zzz');

var_dump($value);


//sleep(1);


$redis = new Redis();
$redis->connect('127.0.0.1', 6381);
//$redis->set('zzz', 'test2');
$value = $redis->get('zzz');

var_dump($value);



echo ' phpredis test  ';
exit;