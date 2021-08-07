<?php


namespace AsmTest\Tests;

use Asm\LostLockException;
use Asm\SessionManager;
use Asm\SessionConfig;
use Asm\Profile\SimpleProfile;
use Asm\ValidationConfig;
use Predis\Client as RedisClient;
use Asm\FailedToAcquireLockException;
use Asm\IdGenerator;
use Asm\IdGenerator\RandomLibIdGenerator;
use Asm\AsmException;
use PHPUnit\Framework\TestCase;
use Laminas\Diactoros\Request;
use Laminas\Diactoros\ServerRequest;

abstract class AbstractSessionTest extends TestCase
{

    /**
     * @var \Auryn\injector
     */
    protected $injector;

    /**
     * @var \Asm\SessionConfig
     */
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;

    private $sessionName = "TestSession";

/**
     * @param IdGenerator $idGenerator
     * @return \Asm\Driver
     */
    abstract public function getDriver(IdGenerator $idGenerator);
    
    /**
     * @param \Asm\ValidationConfig $validationConfig
     * @param \Asm\SimpleProfile $sessionProfile
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

    protected function setUp(): void
    {
        $this->injector = createInjector();
        
        $this->sessionConfig = new SessionConfig(
            $this->sessionName,
            1000,
            60,
            $lockMode = SessionConfig::LOCK_ON_OPEN,
            $lockTimeInMilliseconds = 1000 * 100, //100 seconds
            100
        );
    }

    function testInvalidSessionAccess(): void
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

        $cookies = [
            $this->sessionConfig->getSessionName() => "123456"
        ];

        $request = new ServerRequest();
        $request = $request->withCookieParams($cookies);
        
        $sessionManager = $this->createSessionManager(null, $validationConfig);
        $openSession = $sessionManager->openSessionFromCookie($request);
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
        $request = new ServerRequest();
        $cookies = [
            $this->sessionConfig->getSessionName() => "123456"
        ];
        $request = $request->withCookieParams($cookies);
        $openSession = $sessionLoader->openSessionFromCookie($request);
        $this->assertNull($openSession);
    }

    function testSessionSendsHeaders()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $request = new ServerRequest();
        $session = $sessionManager->createSession($request);

        $headers = $session->getHeaders(\Asm\SessionManager::CACHE_PRIVATE);

        //test key is set-cookie,
        // test value has name TestSession
    }

    /**
     * Create a session then open it with open.
     */
    function testCreateSessionThenReopenThroughCookie()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $request = new ServerRequest();
        $session = $sessionManager->createSession($request);
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        
        //Sessions are inactive by default.
        $this->assertFalse($session->isActive());
        
        $session->setData($srcData);
        //Sessions are active after having data set.
        $this->assertTrue($session->isActive());
        $session->save();
        $session->close();

        $request2 = createRequestFromSessionResponseHeaders($session);


        $sessionManager2 = $this->createSessionManager();
        $reopenedSession = $sessionManager2->openSessionFromCookie($request2);
        $this->assertInstanceOf(\Asm\Session::class, $reopenedSession);
        $dataLoaded = $reopenedSession->getData();
        $this->assertEquals($srcData, $dataLoaded);
        
        $this->assertTrue($reopenedSession->isActive());
    }
    

    // Create a session then reopen it with createSession
    function testCreateSessionThenRecreate()
    {
        $request = new ServerRequest();

        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $newSession = $sessionManager->createSession($request);
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        $newSession->setData($srcData);
        $newSession->save();
        $newSession->close();

        $request2 = createRequestFromSessionResponseHeaders($newSession);

        $sessionManager2 = $this->createSessionManager();
        $reopenedSession = $sessionManager2->createSession($request2);
        $this->assertNotNull($reopenedSession, "Failed to re-open session");
        
        $dataRead = $reopenedSession->getData();
        $this->assertEquals($srcData, $dataRead);
        $this->assertInstanceOf(\Asm\Session::class, $reopenedSession);
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
    // a new
    function testCreateSessionDeleteThenReopen()
    {
        $cookieData = [];
        $sessionManager = $this->createSessionManager();
        $request = new ServerRequest();
        $newSession = $sessionManager->createSession($request);
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->delete();

        $request = createRequestFromSessionResponseHeaders($newSession);
        $sessionManager2 = $this->createSessionManager();
        //The session should no longer exist.
        $reopenedSession = $sessionManager2->openSessionFromCookie($request);
        $this->assertNull($reopenedSession, "Somehow reopened session, even though deleted");
    }


    // Create a session, delete it, then attempt to re-open
    function testCreateSessionDeleteThenReopenThroughSessionManager()
    {
        $sessionManager = $this->createSessionManager();
        $request = new ServerRequest();
        $newSession = $sessionManager->createSession($request);
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $sessionManager->deleteSession($sessionID);

//        $cookieData = [
//            $this->sessionName => $sessionID
//        ];

        $sessionManager2 = $this->createSessionManager();
        //The session should no longer exist.

        $request = createRequestFromSessionResponseHeaders($newSession);
        $reopenedSession = $sessionManager2->openSessionFromCookie($request);
        $this->assertNull($reopenedSession);
//        $reopenedSession
//
//        $this->assertNotEquals(
//            $sessionID,
//            $reopenedSession->getSessionId()
//        );
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

        $profileChange = function (SessionManager $sessionManager, $newProfile, array $previousProfiles) use (&$profileChangeCalledCount, $originalProfile, $differentProfile) {

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
            new ServerRequest(),
            $originalProfile->__toString()
        );
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

//        $cookieData = [
//            $this->sessionName => $sessionID
//        ];
//
        $request = createRequestFromSessionResponseHeaders($newSession);

        $reopenedSession = $sessionManager->createSession(
            $request,
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
        $session = $sessionManager->createSession(new ServerRequest());
        $session->renewLock(1000);
    }

    function testForceReleaseLockAndRenew()
    {
        $sessionManager1 = $this->createSessionManager();
        $session1 = $sessionManager1->createSession(new ServerRequest());
        $sessionManager2 = $this->createSessionManager();
        $request = createRequestFromSessionResponseHeaders($session1);
        $session1->close();
        //$session1->forceReleaseLocks();

        $session2 = $sessionManager2->openSessionFromCookie($request);
        $this->assertNotNull($session2, "Failed to re-open session");
        $session2->forceReleaseLocks();
        $this->expectException('Asm\LostLockException');
        $session1->renewLock(1000);
    }

    function testForceReleaseLockAndValidate()
    {
        $sessionManager1 = $this->createSessionManager();
        $session1 = $sessionManager1->createSession([]);
        $sessionManager2 = $this->createSessionManager(\Asm\SessionConfig::LOCK_MANUALLY);
        $session2 = $sessionManager2->openSessionByID($session1->getSessionId());
        $this->assertNotNull($session2, "Failed to re-open session");
        $session2->forceReleaseLocks();

        $validLock = $session1->validateLock();
    }

    function testLockException()
    {
        $sessionManager = $this->createSessionManager();

        $session = $sessionManager->createSession(new ServerRequest());

        $cookieData = [
            $this->sessionName => $session->getSessionId()
        ];
        $request = new ServerRequest();
        $request = $request->withCookieParams($cookieData);


        try {
            $reopenedSession = $sessionManager->createSession($request);
            $this->fail("FailedToAcquireLockException should have been thrown.");
        }
        catch (FailedToAcquireLockException $ftale) {
        }
        $session->close(false);
    }
    
    function testAcquireLockCoverage()
    {
        $sessionManager = $this->createSessionManager(\Asm\SessionConfig::LOCK_MANUALLY);
        $session = $sessionManager->createSession(new ServerRequest());
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

        $session1 = $sessionManager1->createSession(new ServerRequest());
//        var_dump($session1->getHeaders(\ASM\SessionManager::CACHE_PRIVATE));
//        exit(0);
//
//        $request = new ServerRequest();
//        $request = $request->withCookieParams();


        $session2 = $sessionManager1->openSessionByID($session1->getSessionId());
        $session1->acquireLock(2000, 200);
        $session2->forceReleaseLocks();
        $session2->acquireLock(2000, 50);//This requires IO to not take 50ms...
        $this->expectException(\Asm\LostLockException::class);
        $session1->renewLock(1000);
    }
    
    
    function testGetHeaders()
    {
        $sessionManager1 = $this->createSessionManager();

        $request = new ServerRequest();

        $session1 = $sessionManager1->createSession($request);
        $headerLines = $session1->getHeaders(SessionManager::CACHE_PRIVATE);

        $setCookieSet = false;
        $cacheControlValue = null;

        foreach ($headerLines as $headerLine) {
            list ($key, $value) = $headerLine;

            if (strcasecmp('Set-Cookie', $key) === 0) {
                $setCookieSet = true;
            }
            else if (strcasecmp('Cache-Control', $key) === 0) {
                $cacheControlValue = $value;
            }
        }

        $this->assertTrue($setCookieSet);
        $this->assertEquals("private", $cacheControlValue);
    }
    
    
    
    
    //$session->getHeaders();
//delete
// acquireLock
// isLocked
}
