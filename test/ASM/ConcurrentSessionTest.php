<?php


namespace ASM\Tests;

use ASM\Session;
use ASM\SessionConfig;
use ASM\SimpleProfile;
use ASM\ValidationConfig;

use Predis\Client as RedisClient;
use ASM\ConcurrentSession;


class ConcurrentSessionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Auryn\Provider
     */
    private $provider;
    
    private $sessionConfig;

    private $redisConfig;
    
    private $redisOptions;


    /**
     *
     */
    protected function setUp() {
        $this->provider = createProvider();
        $this->sessionConfig = new SessionConfig(
            'SessionTest',
            1000,
            60
        );

        $this->redisConfig = getRedisConfig();
        $this->redisOptions = getRedisOptions();
    }


//    /**
//     * @return ConcurrentSession
//     */
//    function createEmptySession(ValidationConfig $validationConfig = null, SimpleProfile $sessionProfile = null) {
//
//        $redisClient1 = new RedisClient($this->redisConfig, $this->redisOptions);
//        $mockCookie = array();
//        $session1 = new ConcurrentSession($this->sessionConfig, Session::READ_ONLY, $mockCookie, $redisClient1, $validationConfig, $sessionProfile);
//        $session1->start();
//
//        return $session1;
//    }

    /**
     * @param Session $session1
     * @param ValidationConfig $validationConfig
     * @param SimpleProfile $sessionProfile
     * @return ConcurrentSession
     */
//    function createSecondSession(Session $session1, ValidationConfig $validationConfig = null, SimpleProfile $sessionProfile = null) {
//        $cookie = extractCookie($session1->getHeader());
//        $this->assertNotNull($cookie);
//        $redisClient2 = new RedisClient($this->redisConfig, $this->redisOptions);
//        $mockCookies2 = array_merge(array(), $cookie);
//        $session2 = new Session($this->sessionConfig, Session::READ_ONLY, $mockCookies2, $redisClient2, $validationConfig, $sessionProfile);
//        $session2->start();
//
//        return $session2;
//    }


    /**
     * 
     */
//    function testAsyncIncrement() {
//        $session1 = $this->createEmptySession();
//        $session2 = $this->createSecondSession($session1);
//        $session1->increment('upload');
//        $value1 = $session2->get('upload');
//        $this->assertEquals(1, $value1);
//        $session1->set('percentComplete', '50');
//        $value2 = $session2->get('percentComplete');
//        $this->assertEquals('50', $value2);
//    }

    /**
     * 
     */
//    function testAsyncList() {
//        $session1 = $this->createEmptySession();
//        $session2 = $this->createSecondSession($session1);
//        $list = 'foo';
//        $session1->appendToList($list, 'bar');
//        $result = $session2->getList($list);
//        $this->assertCount(1, $result);
//        $this->assertEquals('bar', $result[0]);
//        $session1->clearList($list);
//        $result = $session2->getList($list);
//        $this->assertEmpty($result);
//    }

    function testBeQuietPHPUnit() {

    }
}