<?php

namespace AsmTest\Predis;

use Asm\LostLockException;
use AsmTest\Tests\AbstractDriverTest;

/**
 * Class RedisDriverTest
 *
 */
class PredisDriverTest extends AbstractDriverTest
{

    /**
     * @return \Asm\Predis\PredisDriver
     */
    function getDriver()
    {
        $redisClient = $this->injector->make(\Predis\Client::class);
        checkClient($redisClient, $this);

        return $this->injector->make(\Asm\Predis\PredisDriver::class);
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
        catch (LostLockException $lle) {
            //This is expected behaviour.
        }
    }
}
