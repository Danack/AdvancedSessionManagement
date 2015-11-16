<?php

namespace ASMTest\Tests;

use ASM\LostLockException;

/**
 * Class RedisDriverTest
 *
 */
class RedisDriverTest extends AbstractDriverTest {

    /**
     * @return \ASM\Redis\RedisDriver
     */
    function getDriver() {
        $redisClient = $this->injector->make('Predis\Client');
        checkClient($redisClient, $this);

        return $this->injector->make('ASM\Redis\RedisDriver');
    }

    /**
     * @group redis
     */
    function testRenewLostLostGivesException()
    {
        $driver = $this->getDriver();
        $sessionID = "testRenewLostLostGivesException12345";
        $lockToken = $driver->acquireLock($sessionID, 100000, 100);
        $driver->renewLock($sessionID, $lockToken, 100000);
        try {
            $driver->renewLock($sessionID, "WrongToken", 100000);
            $this->fail("Renewing a lock acquired elsewhere lost failed to fail");
        }
        catch(LostLockException $lle) {
            //This is expected behaviour.
        }
    }
}
