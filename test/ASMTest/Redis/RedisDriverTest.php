<?php

namespace ASMTest\Tests;


/**
 * Class RedisDriverTest
 *
 */
class RedisDriverTest extends AbstractDriverTest {

    function getDriver() {
        $redisClient = $this->injector->make('Predis\Client');
        checkClient($redisClient, $this);

        return $this->injector->make('\ASM\Redis\RedisDriver');
    }
}