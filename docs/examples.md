An example

```php
use Redis\ClientSentinel\Adapter\PredisClientAdapter;
use Redis\ClientSentinel\Adapter\SocketClientAdapter;
use Redis\ClientSentinel\Adapter\NullClientAdapter;
use Redis\ClientSentinel\Adapter\Predis\ClientCreator as SentinelCreator;
use Redis\Client\Adapter\Predis\ClientCreator as RedisCreator;
use Redis\ClientSentinel;
use Redis\SentinelSet;

use Redis\Client\Adapter\PredisClientAdapter as ClientAdapter;
use Redis\Client\Adapter\PhpRedisClientAdapter;

//$redisLibraryAdapter = new PredisClientAdapter(new SentinelCreator());
//$redisLibraryAdapter = new PredisClientAdapter();
//$redisLibraryAdapter = new NullClientAdapter();
$redisLibraryAdapter = new SocketClientAdapter();

$sentinel1 = new ClientSentinel('127.0.0.1', 26379, $redisLibraryAdapter);
$sentinel2 = new ClientSentinel('127.0.0.1', 26380, $redisLibraryAdapter);
$sentinel3 = new ClientSentinel('127.0.0.1', 26381, $redisLibraryAdapter);

$sentinelSet = new SentinelSet('mymaster', new PhpRedisClientAdapter());

$sentinelSet->addSentinel($sentinel1);
$sentinelSet->addSentinel($sentinel2);
$sentinelSet->addSentinel($sentinel3);

try {
    $redis = $sentinelSet->getMaster();
    $client = $redis->getClientAdapter()->getClient();
} catch (\Exception $e ) {
    
    print_r($e->getMessage());
    exit('something wrong');
}

$client->set('keyname', '-b-');
echo ' '. $client->get('keyname');

echo ' done            ';

var_dump($client);
exit;

# Recover connections #

1. Do master discovery again (maybe with incremental backoff)
2. Retry failed command

# Master discovery #

1. connect to first/next sentinel
2. if successfull, ask with SENTINEL command who the master is
3. if not, connect to next sentinel (back to 1)
4. connect to found master