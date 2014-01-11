<?php


namespace Intahwebz\ASM\Tests;

use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;

use Predis\Client as RedisClient;

$debug = false;

function extractCookie(array $headers) {
    foreach ($headers as $header) {
        if (stripos($header, 'Set-Cookie') === 0) {
            $matches = array();
            $regex = "/Set-Cookie: (\w*)=(\w*);.*/";
            $count = preg_match($regex, $header, $matches, PREG_OFFSET_CAPTURE);

            if ($count == 1) {
                return array($matches[1][0] => $matches[2][0]);
            }
        }
    }

    return null;
}


class SessionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;
    
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

        $this->redisOptions = array(
            'profile' => '2.6',
            //'prefix' => 'sessionTest:',
        );

        //$session = $this->provider->make(\Intahwebz\ASM\Session::class);        
        //$this->provider->share($sessionConfig);
    }

    function testLock() {
        $session = $this->createEmptySession();
        $session->acquireLock();
        $lockReleased = $session->releaseLock();
        $this->assertTrue($lockReleased, "Failed to confirm lock was released.");
    }

    function testForceReleaseLock() {
        $session1 = $this->createEmptySession();
        $session1->acquireLock();
        $session2 = $this->createSecondSession($session1);
        
        $this->assertEquals($session2->getSessionID(), $session1->getSessionID(), "Failed to re-open session with cookie.");

        $session2->forceReleaseLock();

        $lockReleased = $session1->releaseLock();
        $this->assertFalse($lockReleased, "Lock was not force released by second session.");
    }

    /**
     * @return Session
     */
    function createEmptySession() {

        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookie = array();
        $session1 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1);

        return $session1;
    }
    
    
    function createSecondSession(Session $session1) {
        $cookie = extractCookie($session1->getHeaders());
        $this->assertNotNull($cookie);
        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookies2 = array_merge(array(), $cookie);
        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);
        return $session2;
    }
    
    function testStoresData() {
        $session1 = $this->createEmptySession();
        $sessionData = $session1->getData();

        $this->assertEmpty($sessionData);
        $sessionData['testKey'] = 'testValue';
        $session1->setData($sessionData);
        
        $session1->close();
        $session2 = $this->createSecondSession($session1);
        $readSessionData = $session2->getData();

        $this->assertArrayHasKey('testKey', $readSessionData);
        $this->assertEquals($readSessionData['testKey'], 'testValue');
    }
    
    function testZombieSession() {
        $session1 = $this->createEmptySession();
        $cookie = extractCookie($session1->getHeaders());
        $this->assertNotNull($cookie);

        //TODO - regenerating key before setData generates exception

        $sessionData['testKey'] = 'testValue';
        $session1->setData($sessionData);
        $session1->close();

        $session1->regenerateSessionID();


        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookies2 = array_merge(array(), $cookie);
        //Session 2 will now try to open a zombie session.
        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);

        $readSessionData = $session2->getData();
        $this->assertArrayHasKey('testKey', $readSessionData);
        $this->assertEquals($readSessionData['testKey'], 'testValue');
    }
}

 