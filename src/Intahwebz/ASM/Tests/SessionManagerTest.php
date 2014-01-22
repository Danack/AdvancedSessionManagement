<?php


namespace Intahwebz\ASM\Tests;

use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;
use Intahwebz\ASM\SessionManager;
use Intahwebz\ASM\SessionProfile;
use Intahwebz\ASM\ValidationConfig;

use Predis\Client as RedisClient;


class SessionManagerTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;
    
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;


    /**
     * @return Session
     */
    function createEmptySession(ValidationConfig $validationConfig = null, SessionProfile $sessionProfile = null) {

        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookie = array();
        $session1 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1, $validationConfig, $sessionProfile);
        $session1->start();

        return $session1;
    }


    function createSecondSession(Session $session1, ValidationConfig $validationConfig = null,SessionProfile $sessionProfile = null) {
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

        //$session = $this->provider->make(\Intahwebz\ASM\Session::class);        
        //$this->provider->share($sessionConfig);
    }

    function testDeleteSession() {

        $session1 = $this->createEmptySession();
        $sessionData = $session1->getData();
        $sessionData['foo'] = 'bar';
        $session1->setData($sessionData);
        $session1->close();

        $redisClient = new RedisClient($this->redisConfig, $this->redisOptions);
        
        $sessionManager = new SessionManager($this->sessionConfig, $redisClient);
        $sessionManager->deleteSession($session1->getSessionID());

        $session2 = $this->createSecondSession($session1);
        $readSessionData = $session2->getData();

        $this->assertEmpty($readSessionData);
    }


}