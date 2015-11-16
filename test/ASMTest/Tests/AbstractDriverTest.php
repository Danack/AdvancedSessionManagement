<?php

namespace ASMTest\Tests;

use ASM\SessionConfig;
use ASM\SessionManager;
use ASM\LostLockException;

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

    /**
     *
     */
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
        
        $lockValid = $session->validateLock();
        $this->assertFalse($lockValid);

        $session->__destruct();
    }

    function testDestructOfSessionUnlocks()
    {
        $driver = $this->getDriver();
        
        $lockTimeinMS = 5000;
        $sessionConfig = new SessionConfig(
            'testSession',
            3600,
            10,
            \ASM\SessionConfig::LOCK_ON_OPEN,
            $lockTimeinMS,
            100
        );
        $sessionManager = new SessionManager($sessionConfig, $driver);
        $openSession = $driver->createSession($sessionManager);
        $sessionID = $openSession->getSessionId();

        //This will release the lock. 
        $openSession->__destruct();
        $openSession = null;
        
        //This should work instantly as the lock should have been released by the destruct.
        $openSession = $driver->openSessionByID($sessionID, $sessionManager);
    }

    /**
     * @group debugging
     */
    function testRenewLockWithoutAnyOtherAccessSucceeds()
    {
        $driver = $this->getDriver();
        $lockTimeinMS = 100;
        $sessionConfig = new SessionConfig(
            'testSession',
            3600,
            10,
            \ASM\SessionConfig::LOCK_ON_OPEN,
            $lockTimeinMS,
            100
        );
        $sessionManager = new SessionManager($sessionConfig, $driver);
        $openSession = $driver->createSession($sessionManager);
        $openSession->renewLock($lockTimeinMS);
    }

    /**
     * @group failing
     */
    function testRenewLockWithOtherSessionClaimingLockFails()
    {
        $driver = $this->getDriver();
        $lockTimeinMS = 200;
        $sessionConfig = new SessionConfig(
            'testSession',
            3600,
            10,
            \ASM\SessionConfig::LOCK_MANUALLY,
            $lockTimeinMS,
            100
        );
        
        
        $sessionManager = new SessionManager($sessionConfig, $driver);
        $openSession = $driver->createSession($sessionManager);
        $openSession->acquireLock($lockTimeinMS, 100);

        //Sleep long enough for the lock to expire.
        usleep($lockTimeinMS * 1000 * 3);

        $duplicateSession = $driver->openSessionByID(
            $openSession->getSessionId(),
            $sessionManager
        );

        $duplicateSession->acquireLock(
            50000,
            100
        );

        try {
            $openSession->renewLock($lockTimeinMS);
            $this->fail("Renewing a lock that another session has acquired should throw an exception.");
        }
        catch(\ASM\AsmException $ae) {
            //This is expected.
        }

        $validLock = $duplicateSession->validateLock();
        $this->assertTrue($validLock);
    }


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
}




 