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
        checkClient($redisClient, $this);

        return $this->injector->make(
            'ASM\Redis\RedisDriver',
            ['ASM\IdGenerator' => $idGenerator]
        );
    }
}
