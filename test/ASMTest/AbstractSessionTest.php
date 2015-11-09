<?php


namespace ASMTest\Tests;

use ASM\SessionManager;
use ASM\SessionConfig;
use ASM\Profile\SimpleProfile;
use ASM\ValidationConfig;

use Predis\Client as RedisClient;
use ASM\Redis\RedisDriver;
use ASM\Serializer\JsonSerializer;
use ASM\FailedToAcquireLockException;
use ASM\IdGenerator;
use ASM\IdGenerator\RandomLibIdGenerator;

abstract class AbstractSessionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\injector
     */
    protected $injector;

    /**
     * @var \ASM\SessionConfig
     */
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;

    private $sessionName = "TestSession";

/**
     * @param IdGenerator $idGenerator
     * @return \ASM\ConcurrentDriver
     */
    abstract public function getDriver(IdGenerator $idGenerator);
    
    /**
     * @param \ASM\ValidationConfig $validationConfig
     * @param \ASM\SimpleProfile $sessionProfile
     * @return SessionManager
     */
    function createSessionManager(
        $lockMode = null,
        ValidationConfig $validationConfig = null,
        SimpleProfile $sessionProfile = null
    ) {
//        $redisClient = new RedisClient($this->redisConfig, $this->redisOptions);
//        checkClient($redisClient, $this);
//        $serializer = new JsonSerializer();
//        $redisDriver = new RedisDriver($redisClient, $serializer);
        
        $idGenerator = new RandomLibIdGenerator();
        
        $driver = $this->getDriver($idGenerator);
        
        $config = clone $this->sessionConfig;
        if ($lockMode != null) {
            $config->lockMode = $lockMode;
        }
        
        $sessionManager = new SessionManager(
            $config,
            $driver,
            $validationConfig
        );

        return $sessionManager;
    }


//    function createSecondSession(Session $session1, ValidationConfig $validationConfig = null,SimpleProfile $sessionProfile = null) {
//        $cookie = extractCookie($session1->getHeader());
//        $this->assertNotNull($cookie);
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//        $mockCookies2 = array_merge(array(), $cookie);
//        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig, $sessionProfile);
//
//        $session2->start();
//
//        return $session2;
//    }


    protected function setUp() {
        $this->injector = createProvider();
        
        $this->sessionConfig = new SessionConfig(
            $this->sessionName,
            1000,
            60,
            $lockMode = SessionConfig::LOCK_ON_OPEN,
            $lockTimeInMilliseconds = 1000 * 100, //100 seconds
            100
        );
    }
    
//    function getFileDriver()
//    {
//        $path = "./sesstest/subdir".rand(1000000, 10000000);
//        @mkdir($path, 0755, true);
//
//        return $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);
//
//    }

    function testInvalidSessionAccess()
    {
        $wasCalled = false;

        $invalidAccessCallable = function (SessionManager $session) use (&$wasCalled) {
            $wasCalled = true;
        };

        $validationConfig = new ValidationConfig(
            null,
            null,
            $invalidAccessCallable,
            null
        );

        $sessionID = "123456";
        
        $sessionLoader = $this->createSessionManager(null, $validationConfig);
        $openSession = $sessionLoader->openSessionByID($sessionID);
        $this->assertNull($openSession);
        $this->assertTrue($wasCalled, "invalidAccessCallable was not called.");
    }

    /**
     * This just covers the case when there is no invalidAccessCallable set
     */
    function testCoverageInvalidSessionDoesNothing()
    {
        $sessionID = "123456";
        $sessionLoader = $this->createSessionManager();
        $openSession = $sessionLoader->openSessionByID($sessionID);
        $this->assertNull($openSession);
    }

    /**
     * Create a session then open it with open.
     * @group debugging
     */
    function testCreateSessionThenReopenThroughCookie()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $newSession = $sessionManager->createSession($cookieData);
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionManager2 = $this->createSessionManager();
        $reopenedSession = $sessionManager2->openSessionFromCookie($cookieData);        
        $this->assertInstanceOf('ASM\Session', $reopenedSession);
        $dataLoaded = $reopenedSession->getData();
        $this->assertEquals($srcData, $dataLoaded);
    }
    

    // Create a session then reopen it with createSession
    function testCreateSessionThenRecreate()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $newSession = $sessionManager->createSession($cookieData);
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionManager2 = $this->createSessionManager();
        $reopenedSession = $sessionManager2->createSession($cookieData);
        $this->assertInstanceOf('ASM\Session', $reopenedSession);
    }


    // Create a session, delete it, then attempt to re-open
    function testCreateSessionDeleteThenReopen()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $newSession = $sessionManager->createSession($cookieData);
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->delete();

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionManager2 = $this->createSessionManager();
        //The session should no longer exist.
        $reopenedSession = $sessionManager2->openSessionFromCookie($cookieData);
        $this->assertNull($reopenedSession);
    }


    // Create a session, delete it, then attempt to re-open
    function testCreateSessionDeleteThenReopenThroughSessionManager()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $newSession = $sessionManager->createSession($cookieData);
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $sessionManager->deleteSession($sessionID);

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionManager2 = $this->createSessionManager();
        //The session should no longer exist.
        $reopenedSession = $sessionManager2->openSessionFromCookie($cookieData);
        $this->assertNull($reopenedSession);
    }
    
    
    
    


//    function testLock() {
//        $session = $this->createEmptySession();
//        $session->acquireLock();
//        $lockReleased = $session->releaseLock();
//        $this->assertTrue($lockReleased, "Failed to confirm lock was released.");
//    }

//    function testForceReleaseLock() {
//        $session1 = $this->createEmptySession();
//        $session1->acquireLock();
//
//        $cookie = extractCookie($session1->getHeader());
//        $this->assertNotNull($cookie);
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//        $mockCookies2 = array_merge(array(), $cookie);
//
//        $sessionConfig = new SessionConfig(
//            'SessionTest',
//            1000,
//            60,
//            SessionConfig::LOCK_ON_WRITE
//        );
//        
//        $session2 = new Session($sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);
//
//        $session2->forceReleaseLock();
//        
//        $session2->start();
//        
//        
//        $this->assertEquals($session2->getSessionID(), $session1->getSessionID(), "Failed to re-open session with cookie.");
//
//        $lockReleased = $session1->releaseLock();
//        $this->assertFalse($lockReleased, "Lock was not force released by second session.");
//    }


    
//    function testStoresData() {
//        $session1 = $this->createEmptySession();
//        $sessionData = $session1->getData();
//
//        $this->assertEmpty($sessionData);
//        $sessionData['testKey'] = 'testValue';
//        $session1->setData($sessionData);
//
//        $session1->close();
//        $session2 = $this->createSecondSession($session1);
//        $readSessionData = $session2->getData();
//
//        $this->assertArrayHasKey('testKey', $readSessionData);
//        $this->assertEquals($readSessionData['testKey'], 'testValue');
//    }
    
//    function testZombieSession() {
//        $session1 = $this->createEmptySession();
//        $cookie = extractCookie($session1->getHeader());
//        $this->assertNotNull($cookie);
//
//        //TODO - regenerating key before setData generates exception
//        $sessionData['testKey'] = 'testValue';
//        $session1->setData($sessionData);
//        $session1->close();
//
//        $session1->regenerateSessionID();
//
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//        $mockCookies2 = array_merge(array(), $cookie);
//        //Session 2 will now try to open a zombie session.
//        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);
//        $session2->start();
//
//        $readSessionData = $session2->getData();
//        $this->assertArrayHasKey('testKey', $readSessionData);
//        $this->assertEquals($readSessionData['testKey'], 'testValue');
//    }


//    function testInvalidSessionCalled() {
//        $mockCookies2 = array();
//        $mockCookies2['SessionTest'] = "This_Does_not_Exist";
//
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//
////        $session->createSession();
//    }


    function testBadKeyGeneration()
    {
        $redisClient = new RedisClient($this->redisConfig, $this->redisOptions);
        
        checkClient($redisClient, $this);

        //This generator always return the same ID.
        $idGenerator = new \ASMTest\Stub\XKCDIDGenerator();
        $redisDriver = new RedisDriver(
            $redisClient,
            null,
            $idGenerator
        );

        $sessionConfig = new SessionConfig(
            $this->sessionName,
            1000,
            60,
            $lockMode = SessionConfig::LOCK_MANUALLY,
            $lockTimeInMilliseconds = 1000 * 30,
            100
        );

        
        $sessionLoader = new SessionManager(
            $sessionConfig,
            $redisDriver
        );
        
        $this->setExpectedException(
            'ASM\AsmException',
            null,
            \ASM\Driver::E_SESSION_ID_CLASS
        );

        $session1 = $sessionLoader->createSession([]);
        $session2 = $sessionLoader->createSession([]);
    }


    // Create a session then reopen it with createSession
    function testProfileChanged()
    {
        $originalProfile = new SimpleProfile("TestAgent", '1.2.3.4');
        $differentProfile = new SimpleProfile("TestAgent", '1.2.3.5');

        $profileChangeCalledCount = 0;

        $profileChange = function(SessionManager $sessionManager, $newProfile, array $previousProfiles) use(&$profileChangeCalledCount, $originalProfile, $differentProfile) {

            $this->assertEquals(
                $newProfile,
                $differentProfile->__toString(),
                "New profile does not match in callback."
            );

            $this->assertCount(1, $previousProfiles);
            $this->assertEquals(
                $originalProfile,
                $previousProfiles[0],
                "Original profile does not match"
            );

            $profileChangeCalledCount++;
            $previousProfiles[] = $newProfile;

            return $previousProfiles;
        };
        
        $validationConfig = new ValidationConfig(
            $profileChange
        );

        $sessionManager = $this->createSessionManager(null, $validationConfig);

        $newSession = $sessionManager->createSession(
            [],
            $originalProfile->__toString()
        );
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $reopenedSession = $sessionManager->createSession(
            $cookieData,
            $differentProfile->__toString()
        );

        $this->assertEquals(
            1,
            $profileChangeCalledCount,
            "The profile changed callback was not called the correct number of times."
        );
    }

    
    function testRenewLockWorks()
    {
        $sessionManager = $this->createSessionManager();
        $session = $sessionManager->createSession([]);
        $session->renewLock(1000);
    }

    function testForceReleaseLockAndRenew()
    {
        $sessionManager1 = $this->createSessionManager();
        $session1 = $sessionManager1->createSession([]);
        $sessionManager2 = $this->createSessionManager(\ASM\SessionConfig::LOCK_MANUALLY);
        $session2 = $sessionManager2->openSessionByID($session1->getSessionId());
        $session2->forceReleaseLocks();
        $this->setExpectedException('ASM\LostLockException');
        $session1->renewLock(1000);
    }

    function testForceReleaseLockAndValidate()
    {
        $sessionManager1 = $this->createSessionManager();
        $session1 = $sessionManager1->createSession([]);
        $sessionManager2 = $this->createSessionManager(\ASM\SessionConfig::LOCK_MANUALLY);
        $session2 = $sessionManager2->openSessionByID($session1->getSessionId());
        $session2->forceReleaseLocks();
        
        $validLock = $session1->validateLock();
    }
    

    function testLockException()
    {
        $sessionManager = $this->createSessionManager();
        $session = $sessionManager->createSession([]);

        $cookieData = [
            $this->sessionName => $session->getSessionId()
        ];

        try {
            $reopenedSession = $sessionManager->createSession($cookieData);
            $this->fail("FailedToAcquireLockException should have been thrown.");
        }
        catch(FailedToAcquireLockException $ftale) {
        }
        $session->close(false);
    }
    
    function testAcquireLockCoverage()
    {
        $sessionManager = $this->createSessionManager(\ASM\SessionConfig::LOCK_MANUALLY);
        $session = $sessionManager->createSession([]);
        $isLocked = $session->isLocked();
        $this->assertFalse($isLocked);
        $session->acquireLock(2000, 100);
        $isLocked = $session->isLocked();
        $this->assertTrue($isLocked);
    }
    
    //$session->getHeaders();
//delete
// acquireLock
// isLocked
    
    
}
