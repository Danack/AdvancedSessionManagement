<?php


namespace ASM\Tests;

use ASM\Session;
use ASM\SessionConfig;
use ASM\SimpleProfile;
use ASM\ValidationConfig;

use Predis\Client as RedisClient;


class SessionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;
    
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;


    /**
     * @param \ASM\ValidationConfig $validationConfig
     * @param \ASM\SimpleProfile $sessionProfile
     * @return Session
     */
    function createEmptySession(ValidationConfig $validationConfig = null, SimpleProfile $sessionProfile = null) {

        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookie = array();
        $session1 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1, $validationConfig, $sessionProfile);
        $session1->start();

        return $session1;
    }


    function createSecondSession(Session $session1, ValidationConfig $validationConfig = null,SimpleProfile $sessionProfile = null) {
        $cookie = extractCookie($session1->getHeader());
        $this->assertNotNull($cookie);
        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookies2 = array_merge(array(), $cookie);
        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig, $sessionProfile);

        $session2->start();

        return $session2;
    }
    

    protected function setUp() {
        $this->provider = createProvider();
        $this->sessionConfig = new SessionConfig(
            'SessionTest',
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


    function testInvalidSessionCalled() {
//        $mockCookies2 = array();
//        $mockCookies2['SessionTest'] = "This_Does_not_Exist";
//
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//
//        $invalidCallbackCalled = false;
//
//        $invalidCallback = function (Session $session, SessionProfile $newProfile = null) use (&$invalidCallbackCalled) {
//            $invalidCallbackCalled = true;
//        };
//
//        $validationConfig = new ValidationConfig(null, null, $invalidCallback);
//        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig);
//
//        $session2->start();
//
//        $this->assertTrue($invalidCallbackCalled, "Callable for an invalid sessionID was not called.");
    }
}

 