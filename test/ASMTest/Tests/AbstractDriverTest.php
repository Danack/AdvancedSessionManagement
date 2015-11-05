<?php

namespace ASMTest\Tests;

use ASM\FailedToAcquireLockException;

use ASM\SessionConfig;
use ASM\SessionManager;

abstract class AbstractDriverTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Injector
     */
    protected $injector;

    protected function setUp() {
        $this->injector = createProvider();
    }

    /**
     * @return \ASM\Driver
     */
    abstract function getDriver();


    function testOpenInvalidSession()
    {
        $driver = $this->getDriver();
        $sessionManager = createSessionManager($driver);
        $driver->openSessionByID("12346", $sessionManager);
    }
    
    function testBasicOpeningDeleting()
    {
        $driver = $this->getDriver();

        $sessionConfig = new SessionConfig(
            'testSession',
            3600,
            10,
            $lockMode = SessionConfig::LOCK_MANUALLY,
            $lockTimeInMilliseconds = 5000,
            $maxLockWaitTimeMilliseconds = 300
        );

        $sessionManager = new SessionManager($sessionConfig, $driver);
        $session = $driver->createSession($sessionManager);
        $this->assertInstanceOf('ASM\Session', $session);
        $duplicateSession1 = $driver->openSessionByID($session->getSessionId(), $sessionManager);
        $this->assertInstanceOf('ASM\Session', $duplicateSession1);
        
        $this->assertEquals(
            $duplicateSession1->getSessionId(),
            $session->getSessionId()
        );
        
        $driver->deleteSessionByID($session->getSessionId());
        $deletedSession = $driver->openSessionByID($session->getSessionId(), $sessionManager);
        $this->assertNull($deletedSession);
    }
    
    
    function testLockFailsToOpen()
    {
        $driver = $this->getDriver();

        $sessionConfig = new SessionConfig(
            'testSession',
            3600,
            10,
            $lockMode = SessionConfig::LOCK_ON_OPEN,
            $lockTimeInMilliseconds = 5000,
            $maxLockWaitTimeMilliseconds = 300
        );

        $sessionManager = new SessionManager($sessionConfig, $driver);
        $session = $driver->createSession($sessionManager);
        $this->assertInstanceOf('ASM\Session', $session);
        
        $this->setExpectedException('ASM\FailedToAcquireLockException');
        //This will throw an exception as the previous session instance is still open.
        $duplicateSession1 = $driver->openSessionByID($session->getSessionId(), $sessionManager);
    }

    function testForceUnLock()
    {
        $driver = $this->getDriver();

        $sessionConfig = new SessionConfig(
            'testSession',
            3600,
            10,
            $lockMode = SessionConfig::LOCK_ON_OPEN,
            $lockTimeInMilliseconds = 5000,
            $maxLockWaitTimeMilliseconds = 300
        );

        $sessionManager = new SessionManager($sessionConfig, $driver);
        $session = $driver->createSession($sessionManager);
        $this->assertInstanceOf('ASM\Session', $session);
        $driver->forceReleaseLockByID($session->getSessionId());
        $duplicateSession1 = $driver->openSessionByID($session->getSessionId(), $sessionManager);
        $this->assertInstanceOf('ASM\Session', $duplicateSession1);
    }

//    function testCreate() {
//        $driver = $this->getDriver();
//        $data = ['foo' => 'bar'.rand(1000000, 1000000000)];
//
//        $sessionManager1 = createSessionManager($driver);
//        $openSession = $driver->createSession($sessionManager1);
//        $openSession->setData($data);
//        $openSession->save();
//        $sessionId = $openSession->getSessionId();
//        $openSession->close();
//
//        $sessionManager2 = createSessionManager($driver);
//        
//        $reopenedSession = $driver->openSession($sessionId, $sessionManager2);
//        $this->assertInstanceOf('ASM\Session', $reopenedSession);
//
//        $readData = $reopenedSession->getData();
//        $this->assertEquals($data, $readData);
//        $reopenedSession->delete();
//
//        //Delete and test no longer openable
//        //$driver->deleteSession($sessionId);
//
//        $sessionManager3 = createSessionManager($driver);
//        $sessionAfterDelete = $driver->openSession($sessionId, $sessionManager3);        
//        $this->assertNull($sessionAfterDelete);
//    }
//
//
//    function testLockFail() {
//        $driver = $this->getDriver();
//
//        $sessionConfig = new SessionConfig(
//            'testSession',
//            3600,
//            10,
//            \ASM\SessionConfig::LOCK_ON_OPEN,
//            10000,
//            10 
//        );
//
//        $sessionManager = new SessionManager($sessionConfig, $driver);
//        $openSession = $driver->createSession($sessionManager);
//        $sessionId = $openSession->getSessionId();
//
//        try {
//            $reopenedSession = $driver->openSession($sessionId, $sessionManager);
//            $this->fail("Re-opening locked session failed to throw FailedToAcquireLockException");
//        }
//        catch (FailedToAcquireLockException $ftale) {
//        }
//
//        $openSession->close();
//    }
//
//
//
//    function testRenewLock()
//    {
//        $driver = $this->getDriver();
//        $lockTimeinMS = 1000;
//        $sessionConfig = new SessionConfig(
//            'testSession',
//            3600,
//            10,
//            \ASM\SessionConfig::LOCK_ON_OPEN,
//            $lockTimeinMS,
//            1000
//        );
//        $sessionManager = new SessionManager($sessionConfig, $driver);
//        $openSession = $driver->createSession($sessionManager);
//        for ($i=0 ; $i<10 ; $i++) {
//            usleep(($lockTimeinMS / 4) * 1000);
//            $openSession->renewLock($lockTimeinMS);
//        }
//
//        $openSession->close();
//    }
//
//    function testDestructOfSessionUnlocks()
//    {
//        $driver = $this->getDriver();
//        
//        $lockTimeinMS = 1000;
//        $sessionConfig = new SessionConfig(
//            'testSession',
//            3600,
//            10,
//            \ASM\SessionConfig::LOCK_ON_OPEN,
//            $lockTimeinMS,
//            1000
//        );
//        $sessionManager = new SessionManager($sessionConfig, $driver);
//        $openSession = $driver->createSession($sessionManager);
//
//        $openSession->__destruct();
//        
//        //$driver->acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS);
////        $driver->loc
////        __destruct
//        
//    }
//
//    function testRenewLockFails()
//    {
//        $driver = $this->getDriver();
//        $lockTimeinMS = 500;
//        $sessionConfig = new SessionConfig(
//            'testSession',
//            3600,
//            10,
//            \ASM\SessionConfig::LOCK_ON_OPEN,
//            $lockTimeinMS,
//            100
//        );
//        $sessionManager = new SessionManager($sessionConfig, $driver);
//        $openSession = $driver->createSession($sessionManager);
//
//        //Sleep long enough for the lock to expire.
//        usleep($lockTimeinMS * 1000 * 3);
//
//        try {
//            $openSession->renewLock($lockTimeinMS);
//            $this->fail("Renewing a lock that has expired should throw an exception.");
//        }
//        catch(\ASM\AsmException $ae) {
//            //This is expected.
//        }
//
//        try {
//            $openSession->close();
//        }
//        catch(\Exception $e) {
//            //The open session
//        }
//    }
    
    
    
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
    
//    function testLockLostException()
//    {
//        $sessionId = "123456";   
//        $driver = $this->getDriver();
//        $lockRandomString = $driver->acquireLock($sessionId, 5000, 100);
//        $this->setExpectedException('ASM\LostLockException');
//        $driver->releaseLock($sessionId, $lockRandomString."Different");
//    }
}




 