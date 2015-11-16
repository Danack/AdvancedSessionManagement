<?php


namespace ASMTest\Tests;

use ASM\LostLockException;
use ASM\SessionManager;
use ASM\SessionConfig;
use ASM\Profile\SimpleProfile;
use ASM\ValidationConfig;
use Predis\Client as RedisClient;
use ASM\FailedToAcquireLockException;
use ASM\IdGenerator;
use ASM\IdGenerator\RandomLibIdGenerator;
use ASM\AsmException;

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

    protected function setUp()
    {
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
     */
    function testCreateSessionThenReopenThroughCookie()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $newSession = $sessionManager->createSession($cookieData);
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        
        //Sessions are inactive by default.
        $this->assertFalse($newSession->isActive());
        
        $newSession->setData($srcData);
        //Sessions are active after having data set.
        $this->assertTrue($newSession->isActive());
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
        
        $this->assertTrue($reopenedSession->isActive());
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
        $this->assertNotNull($reopenedSession, "Failed to re-open session");
        
        $dataRead = $reopenedSession->getData();
        $this->assertEquals($srcData, $dataRead);
        $this->assertInstanceOf('ASM\Session', $reopenedSession);
    }

    
//        // Create a session then reopen it with createSession
//    function testCreateSessionThenRecreateWithArrayReference()
//    {
//        $cookieData = [];
//        $sessionManager = $this->createSessionManager();
//        $newSession = $sessionManager->createSession($cookieData);
//        $srcData = &$newSession->getData();
//        $this->assertEmpty($srcData, "newly created session isn't empty.");
//        $srcData['foo'] = 'bar'.rand(1000000, 1000000);
//        $newSession->save();
//        $sessionID = $newSession->getSessionId();
//        $newSession->close();
//
//        $cookieData = [
//            $this->sessionName => $sessionID
//        ];
//
//        $sessionManager2 = $this->createSessionManager();
//        $reopenedSession = $sessionManager2->createSession($cookieData);
//        $dataRead = $reopenedSession->getData();
//        $this->assertEquals($srcData, $dataRead);
//        $this->assertInstanceOf('ASM\Session', $reopenedSession);
//    }
//    
    
    

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
        
        $driver = $this->getDriver($idGenerator);

        $sessionConfig = new SessionConfig(
            $this->sessionName,
            1000,
            60,
            $lockMode = SessionConfig::LOCK_MANUALLY,
            $lockTimeInMilliseconds = 1000 * 30,
            100
        );

        $sessionManager = new SessionManager(
            $sessionConfig,
            $driver
        );

        $session1 = $sessionManager->createSession([]);

        $this->setExpectedException(
            'ASM\AsmException',
            '',
            AsmException::ID_CLASH
        );
        $session2 = $sessionManager->createSession([]);
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
        $this->assertNotNull($session2, "Failed to re-open session");
        $session2->forceReleaseLocks();
        $this->setExpectedException('Asm\LostLockException');
        $session1->renewLock(1000);
    }

    function testForceReleaseLockAndValidate()
    {
        $sessionManager1 = $this->createSessionManager();
        $session1 = $sessionManager1->createSession([]);
        $sessionManager2 = $this->createSessionManager(\ASM\SessionConfig::LOCK_MANUALLY);
        $session2 = $sessionManager2->openSessionByID($session1->getSessionId());
        $this->assertNotNull($session2, "Failed to re-open session");
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
    
    
    function testLockExpiresAndSecondSessionClaimsLock()
    {
        $sessionConfig = new SessionConfig(
            $this->sessionName,
            1000,
            60,
            $lockMode = SessionConfig::LOCK_MANUALLY,
            $lockTimeInMilliseconds = 2000,
            100
        );
        
        $idGenerator = new RandomLibIdGenerator();
        $driver = $this->getDriver($idGenerator);
        $sessionManager1 = new SessionManager(
            $sessionConfig,
            $driver
        );

        $session1 = $sessionManager1->createSession([]);

        $session2 = $sessionManager1->openSessionByID($session1->getSessionId());
        $session1->acquireLock(2000, 200);
        $session2->forceReleaseLocks();
        $session2->acquireLock(2000, 50);//This requires IO to not take 50ms...
        $this->setExpectedException('ASM\LostLockException');
        $session1->renewLock(1000);
    }
    
    
    function testGetHeaders()
    {
        $sessionManager1 = $this->createSessionManager();
        $session1 = $sessionManager1->createSession([]);
        $headers = $session1->getHeaders(SessionManager::CACHE_PRIVATE);

        $this->assertArrayHasKey('Set-Cookie', $headers);
//        $this->assertRegExp(
//            '#TestSession=\w; expires=.* UTC; Max-Age=1000; httpOnly#',
//            $headers['Set-Cookie']
//        );
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertEquals("private", $headers['Cache-Control']);
    }
    
    
    
    
    //$session->getHeaders();
//delete
// acquireLock
// isLocked
    
    
}
