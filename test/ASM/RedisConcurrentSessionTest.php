<?php


namespace ASM\Tests;

use ASM\IdGenerator;
use Predis\Client as RedisClient;
use ASM\Redis\RedisDriver;


class RedisConcurrentSessionTest extends AbstractConcurrentSessionTest {


    /**
     * @param IdGenerator $idGenerator
     * @return RedisDriver
     */
    public function getDriver(IdGenerator $idGenerator)
    {
        $redisClient = new RedisClient(getRedisConfig(), getRedisOptions());
        $driver = new RedisDriver(
            $redisClient,
            null,
            $idGenerator
        );

        return $driver;
    }

}

