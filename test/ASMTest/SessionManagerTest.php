<?php


namespace ASM\Tests;

use ASM\SessionManager;
use ASM\SessionConfig;
use ASM\ValidationConfig;
use ASMTest\Stub\NullDriver;
use Predis\Client as RedisClient;
use ASM\AsmException;

class SessionManagerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Injector
     */
    private $provider;
    
    private $sessionConfig;

    /**
     * @internal param ValidationConfig $validationConfig
     * @internal param SimpleProfile $sessionProfile
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
        $this->provider = createProvider();

        $this->sessionConfig = new SessionConfig(
            'SessionTest',
            1000,
            60
        );


        $this->redisOptions = getRedisOptions();

        //$session = $this->provider->make(\ASM\Session::class);        
        //$this->provider->share($sessionConfig);
    }

//    function testDeleteSession() {
//        $session1 = $this->createEmptySession();
//        $sessionData = $session1->getData();
//        $sessionData['foo'] = 'bar';
//        $session1->setData($sessionData);
//        $session1->close();
//
//        $redisClient = new RedisClient($this->redisConfig, $this->redisOptions);
//        
//        $sessionManager = new SessionManager($this->sessionConfig, $redisClient);
//        $sessionManager->deleteSession($session1->getSessionID());
//
//        $session2 = $this->createSecondSession($session1);
//        $readSessionData = $session2->getData();
//
//        $this->assertEmpty($readSessionData);
//    }

    function testEmpty() {

    }
    
    
    function testOpenSessionFromCookieNoData()
    {
        $sessionManager = new SessionManager($this->sessionConfig, new NullDriver());
        $session = $sessionManager->openSessionFromCookie([]);
        $this->assertNull($session);
    }
    
        
    function testBadUserProfileComprison()
    {
        $sessionManager = new SessionManager($this->sessionConfig, new NullDriver());
        $this->setExpectedException(
            'ASM\AsmException',
            '',
            AsmException::BAD_ARGUMENT
        );
        $sessionManager->performProfileSecurityCheck(
            new \StdClass,
            []
        );
    }
    

    function testUserProfileChangedCoverage()
    {
        $sessionManager = new SessionManager(
            $this->sessionConfig,
            new NullDriver()
        );
        $existingProfiles = ["ExistingProfile"];
        $returnValue = $sessionManager->performProfileSecurityCheck(
            'NewUAProfile',
            $existingProfiles
        );

        $this->assertEquals($existingProfiles, $returnValue);
    }
    
    function testUserProfileChangedCoverage2()
    {
        $fn = function () {

        };
        $validationConfig = new ValidationConfig(
            $fn
        );
        $sessionManager = new SessionManager(
            $this->sessionConfig,
            new NullDriver(),
            $validationConfig
        );
        $existingProfiles = ["ExistingProfile"];
        $returnValue = $sessionManager->performProfileSecurityCheck(
            'ExistingProfile',
            $existingProfiles
        );

        $this->assertEquals($existingProfiles, $returnValue);
    }
    
    function testUserProfileChangedBadCallable()
    {
        $fn = function () {
            return 4;
        };
        $validationConfig = new ValidationConfig(
            $fn
        );
        $sessionManager = new SessionManager(
            $this->sessionConfig,
            new NullDriver(),
            $validationConfig
        );
        $existingProfiles = ["ExistingProfile"];
        
        $this->setExpectedException(
            'ASM\AsmException',
            '',
            AsmException::BAD_ARGUMENT
        );
        $returnValue = $sessionManager->performProfileSecurityCheck(
            'NewUAProfile',
            $existingProfiles
        );
    }
    
    
    
}