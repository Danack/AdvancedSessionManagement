<?php


namespace Intahwebz\ASM\Tests;

use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;
use Intahwebz\ASM\SessionProfile;
use Intahwebz\ASM\ValidationConfig;

use Predis\Client as RedisClient;

function maskAndCompareIPAddresses($ipAddress1, $ipAddress2, $maskBits) {

    $ipAddress1 = ip2long($ipAddress1);
    $ipAddress2 = ip2long($ipAddress2);

    $mask = (1<<(32 - $maskBits));
    
    if (($ipAddress1 & $mask) == ($ipAddress2 & $mask)) {
        return true;
    }

    return false;
}

function extractCookie($header) {
    if (stripos($header, 'Set-Cookie') === 0) {
        $matches = array();
        $regex = "/Set-Cookie: (\w*)=(\w*);.*/";
        $count = preg_match($regex, $header, $matches, PREG_OFFSET_CAPTURE);

        if ($count == 1) {
            return array($matches[1][0] => $matches[2][0]);
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
        $cookie = extractCookie($session1->getHeader());
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
        $session2->start();

        $readSessionData = $session2->getData();
        $this->assertArrayHasKey('testKey', $readSessionData);
        $this->assertEquals($readSessionData['testKey'], 'testValue');
    }
    
    
    function testChangedUserAgentCallsProfileChanged() {

        $userAgent1 = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.63 Safari/537.36";
        $userAgent2 = "Opera/7.50 (Windows ME; U) [en]";

        $sessionProfile1 = new SessionProfile('1.2.3.4', $userAgent1);
        $sessionProfile2 = new SessionProfile('1.2.3.50', $userAgent2);
        $sessionProfile3 = new SessionProfile('1.2.30.4', $userAgent2);

        $profileChangedCalled = false;

        $profileChangedFunction = function (Session $session, SessionProfile $newProfile, $profileList) use (&$profileChangedCalled) {
            $profileChangedCalled = true;

            foreach ($profileList as $pastProfile) {
                /** @var $pastProfile SessionProfile */
                if (maskAndCompareIPAddresses($newProfile->getIPAddress(), $pastProfile->getIPAddress(), 24) == false) {
                    throw new \InvalidArgumentException("Users ip address has changed, must login again.");
                }
            }
        };

        $validationConfig = new ValidationConfig($profileChangedFunction, null, null);

        $session1 = $this->createEmptySession($validationConfig, $sessionProfile1);

        $sessionData = $session1->getData();
        $sessionData['profileTest'] = true;
        $session1->setData($sessionData);
        $session1->close();

        $session2 = $this->createSecondSession($session1, $validationConfig, $sessionProfile2);
        $this->assertTrue($profileChangedCalled);

        $this->setExpectedException('\InvalidArgumentException');
        $session3 = $this->createSecondSession($session1, $validationConfig, $sessionProfile3);
    }
    
    
    function testInvalidSessionCalled() {
        $mockCookies2 = array();
        $mockCookies2['SessionTest'] = "I_Dont_Exist";

        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);

        $invalidCallbackCalled = false;

        $invalidCallback = function (Session $session, SessionProfile $newProfile = null) use (&$invalidCallbackCalled) {
            $invalidCallbackCalled = true;
        };

        $validationConfig = new ValidationConfig(null, null, $invalidCallback);
        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig);

        $session2->start();

        $this->assertTrue($invalidCallbackCalled, "Callable for an invalid sessionID was not called.");
    }
    
    
    function testAsync() {

        $session1 = $this->createEmptySession();
        $session2 = $this->createSecondSession($session1);
        $session1->asyncIncrement('upload');
        $value1 = $session2->asyncGet('upload');
        $this->assertEquals(1, $value1);
        
        $session1->asyncSet('percentComplete', '50');
        $value2 = $session2->asyncGet('percentComplete');
        $this->assertEquals('50', $value2);

    }

}

 