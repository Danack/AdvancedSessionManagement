<?php


namespace ASM\Tests;

use ASM\SessionManager;
use ASM\SessionConfig;
use ASM\SimpleProfile;
use ASM\ValidationConfig;

use Predis\Client as RedisClient;
use ASM\Redis\RedisDriver;
use ASM\Serializer\JsonSerializer;

class SessionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\injector
     */
    private $injector;
    
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;

    private $sessionName = "TestSession";


    /**
     * @param \ASM\ValidationConfig $validationConfig
     * @param \ASM\SimpleProfile $sessionProfile
     * @return SessionManager
     */
    function createSessionManager($cookieData, ValidationConfig $validationConfig = null, SimpleProfile $sessionProfile = null) {
        $redisClient = new RedisClient($this->redisConfig, $this->redisOptions);
        $serializer = new JsonSerializer();
        $redisDriver = new RedisDriver($redisClient, $serializer);
        $session = new SessionManager(
            $this->sessionConfig,
            $cookieData,
            $redisDriver,
            $validationConfig
        );

        return $session;
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
            60
        );

        $this->redisConfig = array(
            "scheme" => "tcp",
            "host" => '127.0.0.1',
            "port" => 6379
        );

        $this->redisOptions = array(
            'profile' => '2.6',
            'prefix' => 'sessionTest:',
        );

        //$session = $this->provider->make(\ASM\Session::class);        
        //$this->provider->share($sessionConfig);
    }
    
    function getFileDriver()
    {
        $path = "./sesstest/subdir".rand(1000000, 10000000);
        @mkdir($path, true);

        return $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);

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
        
        $cookieData = [
            $this->sessionName => $sessionID,
        ];
        
        $sessionLoader = $this->createSessionManager($cookieData, $validationConfig);
        $openSession = $sessionLoader->openSession();
        $this->assertNull($openSession);
        $this->assertTrue($wasCalled, "invalidAccessCallable was not called.");
    }

    /**
     * This just covers the case when there is no invalidAccessCallable set
     */
    function testCoverageInvalidSessionDoesNothing()
    {
        $sessionID = "123456";

        $cookieData = [
            $this->sessionName => $sessionID,
        ];

        $sessionLoader = $this->createSessionManager($cookieData);
        $openSession = $sessionLoader->openSession();
        $this->assertNull($openSession);
    }

    // Create a session then open it with open.
    function testCreateSessionThenReopen()
    {
        $cookieData = [];
        $sessionLoader = $this->createSessionManager($cookieData);
        $newSession = $sessionLoader->createSession();
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionLoader2 = $this->createSessionManager($cookieData);
        $reopenedSession = $sessionLoader2->openSession();        
        $this->assertInstanceOf('ASM\Session', $reopenedSession);
        $dataLoaded = $reopenedSession->loadData();
        $this->assertEquals($srcData, $dataLoaded);
    }

    // Create a session then reopen it with createSession
    function testCreateSessionThenRecreate()
    {
        $cookieData = [];
        $sessionLoader = $this->createSessionManager($cookieData);
        $newSession = $sessionLoader->createSession();
        $srcData = ['foo' => 'bar'.rand(1000000, 1000000)];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionLoader2 = $this->createSessionManager($cookieData);
        $reopenedSession = $sessionLoader2->createSession();
        $this->assertInstanceOf('ASM\Session', $reopenedSession);
        $dataLoaded = $reopenedSession->loadData();
        $this->assertEquals($srcData, $dataLoaded);
    }


    // Create a session, delete it, then attempt to re-open
    function testCreateSessionDeleteThenReopen()
    {
        $cookieData = [];
        $sessionLoader = $this->createSessionManager($cookieData);
        $newSession = $sessionLoader->createSession();
        $srcData = ['foo' => 'bar'];
        $newSession->setData($srcData);
        $newSession->save();
        $sessionID = $newSession->getSessionId();
        $newSession->close();

        $sessionLoader->deleteSession($sessionID);

        $cookieData = [
            $this->sessionName => $sessionID
        ];

        $sessionLoader2 = $this->createSessionManager($cookieData);
        //The session should no longer exist.
        $reopenedSession = $sessionLoader2->openSession();
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
        
        //This generator always return the same ID.
        $idGenerator = new \ASM\Mock\XKCDIDGenerator();
        $redisDriver = new RedisDriver(
            $redisClient,
            null,
            $idGenerator
        );

        $sessionLoader = new SessionManager(
            $this->sessionConfig,
            $cookieData = [],
            $redisDriver
        );
        
        $this->setExpectedException(
            'ASM\AsmException',
            null,
            \ASM\Driver::E_SESSION_ID_CLASS
        );

        $session1 = $sessionLoader->createSession();
        $session2 = $sessionLoader->createSession();
    }
}

 