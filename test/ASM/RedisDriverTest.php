<?php

namespace ASM\Tests;


class RedisDriverTest extends AbstractDriverTest{
    function getDriver() {
        return $this->injector->make('\ASM\Redis\RedisDriver');
    }
}