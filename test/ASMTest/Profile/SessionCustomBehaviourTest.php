<?php


namespace ASMTest\Tests;

use ASM\SessionManager;
use ASM\SessionConfig;
use ASM\SimpleProfile;
use ASM\ValidationConfig;




class SessionCustomBehaviourTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;

    /**
     * @var \ASM\SessionConfig
     */
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;


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

        $this->redisOptions = getRedisOptions();
    }
    
    
    /**
     * @param \ASM\ValidationConfig $validationConfig
     * @param \ASM\SimpleProfile $sessionProfile
     * @return SessionManager
     */
//    function createEmptySession(ValidationConfig $validationConfig = null, SimpleProfile $sessionProfile = null) {
//
//        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
//        $mockCookie = array();
//        $session1 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1, $validationConfig, $sessionProfile);
//        $session1->start();
//
//        return $session1;
//    }

    /**
     * @param SessionManager $session1
     * @param ValidationConfig $validationConfig
     * @param SimpleProfile $sessionProfile
     * @return SessionManager
     */
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


    /**
     * 
     */
//    function testZombieSessionRegeneration() {
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

    /**
     * 
     */
//    function testZombieSessionDetected() {
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
//        
//
//        $sessionConfig = new SessionConfig(
//            'SessionTest',
//            1000,
//            60,
//            SessionConfig::LOCK_ON_WRITE
//        );
//        
//        
//        $session2 = new Session($sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);
//        $session2->start();
//        $zombieDetectedCallback = false;
//
//        $zombieCallback = function (Session $session, SimpleProfile $newProfile = null) use (&$zombieDetectedCallback) {
//            $zombieDetectedCallback = true;
//        };
//
//        $validationConfig = new ValidationConfig(null, $zombieCallback, null);
//        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig);
//
//        $session2->start();
//
//        $this->assertTrue($zombieDetectedCallback, "Callable for a zombie session detection was not called.");
//    }


    /**
     * 
     */
    function testChangedUserAgentCallsProfileChanged() {

//        $userAgent1 = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36";
//        $userAgent2 = "Opera/7.50 (Windows ME; U) [en]";
//
//        $sessionProfile1 = new SessionProfile('1.2.3.4', $userAgent1);
//        $sessionProfile2 = new SessionProfile('1.2.3.50', $userAgent2);
//        $sessionProfile3 = new SessionProfile('1.2.30.4', $userAgent2);
//
//        $profileChangedCalled = false;
//
//        $profileChangedFunction = function (Session $session, SessionProfile $newProfile, $profileList) use (&$profileChangedCalled) {
//            $profileChangedCalled = true;
//
//            foreach ($profileList as $pastProfile) {
//                /** @var $pastProfile SessionProfile */
//                if (maskAndCompareIPAddresses($newProfile->getIPAddress(), $pastProfile->getIPAddress(), 24) == false) {
//                    throw new \InvalidArgumentException("Users ip address has changed, must login again.");
//                }
//            }
//        };
//
//        $validationConfig = new ValidationConfig($profileChangedFunction, null, null);
//
//        $session1 = $this->createEmptySession($validationConfig, $sessionProfile1);
//
//        $sessionData = $session1->getData();
//        $sessionData['profileTest'] = true;
//        $session1->setData($sessionData);
//        $session1->close();
//
//        $this->createSecondSession($session1, $validationConfig, $sessionProfile2);
//        $this->assertTrue($profileChangedCalled);
//
//        $this->setExpectedException('\InvalidArgumentException');
//        $this->createSecondSession($session1, $validationConfig, $sessionProfile3);

    }

    /**
     * 
     */
//    function testInvalidSessionCalled() {
//        $mockCookies2 = array();
//        $mockCookies2['SessionTest'] = "This_Does_not_Exist";
//
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//
//        $invalidCallbackCalled = false;
//
//        $invalidCallback = function (Session $session, SimpleProfile $newProfile = null) use (&$invalidCallbackCalled) {
//            $invalidCallbackCalled = true;
//        };
//
//        $validationConfig = new ValidationConfig(null, null, $invalidCallback);
//        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig);
//
//        $session2->start();
//
//        $this->assertTrue($invalidCallbackCalled, "Callable for an invalid sessionID was not called.");
//    }
}

 