<?php


namespace Intahwebz\ASM\Tests;

use Intahwebz\ASM\Session;
use Intahwebz\ASM\SessionConfig;
use Intahwebz\ASM\SessionProfile;
use Intahwebz\ASM\ValidationConfig;

use Predis\Client as RedisClient;



class SessionAsyncTest extends \PHPUnit_Framework_TestCase {

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
    }

  
    function testAsyncIncrement() {

        $session1 = $this->createEmptySession();
        $session2 = $this->createSecondSession($session1);
        $session1->asyncIncrement('upload');
        $value1 = $session2->asyncGet('upload');
        $this->assertEquals(1, $value1);
        
        $session1->asyncSet('percentComplete', '50');
        $value2 = $session2->asyncGet('percentComplete');
        $this->assertEquals('50', $value2);
    }


    function testAsyncList() {
        $session1 = $this->createEmptySession();
        $session2 = $this->createSecondSession($session1);
        $list = 'foo';
        $session1->asyncAppendToList($list, 'bar');
        $result = $session2->asyncGetList($list);
        $this->assertCount(1, $result);
        $this->assertEquals('bar', $result[0]);

        $session1->asyncClearList($list);

        $result = $session2->asyncGetList($list);
        $this->assertEmpty($result);
    }

}