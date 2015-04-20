<?php

namespace ASM\Tests;

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var \Auryn\Provider
     */
    protected $injector = null;
    
    protected function setUp() {
        $this->injector = createProvider();
    }

    /**
     * @return \ASM\Driver\Driver
     */
    abstract function getDriver();

    /**
     * Basic lock functionality
     */
    function testLock() {
        $sessionID = 12345;
        $driver = $this->getDriver();
        $this->assertFalse($driver->isLocked($sessionID));
        $driver->acquireLock($sessionID, 1000, 1000);
        $this->assertTrue($driver->isLocked($sessionID), "Driver doesn't think lock is acquired.");
        $this->assertTrue($driver->validateLock($sessionID), "Validating lock failed.");
        $driver->releaseLock($sessionID);
        $this->assertFalse($driver->isLocked($sessionID), "Driver thinks lock is still acquired.");
    }

    /**
     * Test acquiring a lock times out.
     */
    function testLockAcquireTimeout() {
        $sessionID = 12345;
        $this->setExpectedException('ASM\FailedToAcquireLockException');

        $driver1 = $this->getDriver();
        $driver2 = $this->getDriver();

        $driver1->acquireLock($sessionID, 1000, 1000);
        $driver2->acquireLock($sessionID, 1000, 100);
    }

    
    

//    function renewLock($milliseconds);
//
//    function releaseLock();
//

//    function forceReleaseLock($sessionID);
//
//    function findSessionIDFromZombieID($sessionID);
//
//    function setupZombieID($dyingSessionID, $newSessionID, $zombieTimeMilliseconds);
//
//    function save($sessionID, $saveData);
//
//    function destroyExpiredSessions();
//
//    function deleteSession($sessionID);
//
//    function addProfile($sessionID, $sessionProfile);
//
//    function getStoredProfile($sessionID);
//
//    function storeSessionProfiles($sessionProfiles);
//    
    
    
}




 