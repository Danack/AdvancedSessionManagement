<?php

namespace ASM\Tests;

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var \Auryn\Injector
     */
    protected $injector = null;
    
    protected function setUp() {
        $this->injector = createProvider();
    }

    /**
     * @return \ASM\Driver\Driver
     */
    abstract function getDriver();

    
    function testOpenInvalidSession()
    {
        $driver = $this->getDriver();
        $driver->openSession(12345);
    }
    

    function testCreate() {
        $driver = $this->getDriver();
        $data = ['foo' => 'bar'.rand(100000000, 1000000000)];
        
        $openDriver = $driver->createSession();
        $openDriver->save($data);
        $sessionID = $openDriver->getSessionID();
        $openDriver->close();

        $reopenedSession = $driver->openSession($sessionID);
        $this->assertInstanceOf('ASM\Driver\DriverOpen', $reopenedSession);

        $readData = $reopenedSession->readData();
        $this->assertEquals($data, $readData);

        //Delete and test no longer openable
        $driver->deleteSession($sessionID);
        $sessionAfterDelete = $driver->openSession($sessionID);        
        $this->assertNull($sessionAfterDelete);

    }
    
    
//    /**
//     * Basic lock functionality
//     */
//    function testLock() {
//        $sessionID = 12345;
//        $driver = $this->getDriver();
//        $this->assertFalse($driver->isLocked($sessionID));
//        $driver->acquireLock($sessionID, 1000, 1000);
//        $this->assertTrue($driver->isLocked($sessionID), "Driver doesn't think lock is acquired.");
//        $this->assertTrue($driver->validateLock($sessionID), "Validating lock failed.");
//        $driver->releaseLock($sessionID);
//        $this->assertFalse($driver->isLocked($sessionID), "Driver thinks lock is still acquired.");
//    }

//    /**
//     * Test acquiring a lock times out.
//     */
//    function testLockAcquireTimeout() {
//        $sessionID = 12345;
//        $this->setExpectedException('ASM\FailedToAcquireLockException');
//
//        $driver1 = $this->getDriver();
//        $driver2 = $this->getDriver();
//
//        $driver1->acquireLock($sessionID, 1000, 1000);
//        $driver2->acquireLock($sessionID, 1000, 100);
//    }

    
//    function testZombieFunctionality() {
//        $zombieTimeMilliseconds = 1000;
//        $driver1 = $this->getDriver();
//
//        $sessionID = $driver1->createSession();
//        $srcData = ['foo' => 'bar'];
//        $driver1->save($sessionID, $srcData);
//        $newSessionID = $driver1->setupZombieID($sessionID, $zombieTimeMilliseconds);
//        $driver1->close();
//
//        $driver2 = $this->getDriver();
//        
//        $foundSessionID = $driver2->findSessionIDFromZombieID($sessionID);
//        
//        $this->assertNotFalse($foundSessionID, "Failed to find any live sesssion.");
//        $this->assertEquals($newSessionID, $foundSessionID, "Zombie session ID '$sessionID' did not lead to new session ID '$newSessionID' instead got '$foundSessionID'. ");
//
//        $readData = $driver2->openSession($foundSessionID);
//        
//        
//        $this->assertEquals($srcData, $readData, "Data read for session $foundSessionID did not match expected values.");
//    }
    
    
//    function testDeleteClearsEverything() {
//        //
//    }

//    function renewLock($milliseconds);
//
//    function releaseLock();
//

//    function forceReleaseLock($sessionID);
//
//    function 
//
//   
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




 