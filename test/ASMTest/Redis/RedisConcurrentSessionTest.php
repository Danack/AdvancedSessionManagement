<?php


namespace ASMTest\Tests;

use ASM\IdGenerator;
use Predis\Client as RedisClient;
use ASM\Redis\RedisDriver;


class RedisConcurrentSessionTest extends AbstractConcurrentSessionTest
{
    /**
     * @param IdGenerator $idGenerator
     * @return RedisDriver
     */
    public function getDriver(IdGenerator $idGenerator)
    {
        $redisClient = $this->injector->make('Predis\Client');

        try {
            /** @var $redisClient \Predis\Client */
            $result = $redisClient->ping("Shamoan");
            if ($result != "Shamoan") {
                throw new \Exception("Redis ping is broken"); 
            }
        }
        catch (\Exception $e) {
            $this->markTestSkipped("Redis unavailable");
        }
        
        
        $redisClient = new RedisClient(getRedisConfig(), getRedisOptions());
        $driver = new RedisDriver(
            $redisClient,
            null,
            $idGenerator
        );

        return $driver;
    }

}

