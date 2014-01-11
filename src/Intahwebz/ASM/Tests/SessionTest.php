<?php


namespace Intahwebz\ASM\Tests;

use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;

use Predis\Client as RedisClient;

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
            10
        );

        $this->redisConfig = array(
            "scheme" => "tcp",
            "host" => '127.0.0.1',
            "port" => 6379
        );

        $this->redisOptions = array(
            'profile' => '2.6',
            'prefix' => 'sessionTest',
        );

        //$session = $this->provider->make(\Intahwebz\ASM\Session::class);        
        //$this->provider->share($sessionConfig);
    }

    function testLock() {
        $redisClient = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookie = array();
        $session = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient);
        $session->acquireLock();
        $lockReleased = $session->releaseLock();
        $this->assertTrue($lockReleased, "Failed to confirm lock was released.");
    }

    function testForceReleaseLock() {
        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookie = array();
        $session1 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1);
        $session1->acquireLock();

        $cookie = extractCookie($session1->getHeaders());

        $this->assertNotNull($cookie);

        $mockCookies2 = array_merge(array(), $cookie);
        
        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);
        
        $this->assertEquals($session2->getSessionID(), $session1->getSessionID(), "Failed to re-open session with cookie.");

        $session2->forceReleaseLock();

        $lockReleased = $session1->releaseLock();
        $this->assertFalse($lockReleased, "Lock was not force released by second session.");
    }

    function testStoresData() {
        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookie = array();
        $session1 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1);
        
        $sessionData = $session1->getData();

        $this->assertEmpty($sessionData);
        $sessionData['testKey'] = 'testValue';
        $session1->setData($sessionData);
        
        $session1->close();

        $cookie = extractCookie($session1->getHeaders());

        $this->assertNotNull($cookie);

        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
        $mockCookies2 = array_merge(array(), $cookie);

        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2);
        $readSessionData = $session2->getData();

        $this->assertArrayHasKey('testKey', $readSessionData);
        $this->assertEquals($readSessionData['testKey'], 'testValue');
    }
}

 