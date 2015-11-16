<?php


namespace ASMTest\Tests;

use ASM\Redis\RedisSession;
use ASM\ConcurrentSessionManager;
use ASM\SessionConfig;
use ASM\SimpleProfile;
use ASM\ValidationConfig;

use Predis\Client as RedisClient;
use ASM\Redis\RedisDriver;
use ASM\ConcurrentSession;
use ASM\IdGenerator;


abstract class AbstractConcurrentSessionTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var \ASM\ConcurrentSessionManager
     */
    private $sessionManager;
    
    /**
     * @return \ASM\Driver
     */
    //abstract function getConcurrentDriver();

    /**
     * @var \ASM\Redis\RedisDriver
     */
    private $redisDriver;

    /**
     * @var \Auryn\Injector
     */
    protected $injector;
    
    /**
     *
     */
    protected function setUp()
    {    
        $this->injector = createProvider();

        $sessionConfig = new SessionConfig(
            'SessionTest',
            1000,
            60
        );

        $idGenerator = new \ASM\IdGenerator\RandomLibIdGenerator();
        $this->redisDriver = $this->getDriver($idGenerator);

        $this->sessionManager = new ConcurrentSessionManager(
            $sessionConfig,
            $this->redisDriver
        );
    }

    /**
     * @param IdGenerator $idGenerator
     * @return \ASM\ConcurrentDriver
     */
    abstract public function getDriver(IdGenerator $idGenerator);


    /**
     * @param ValidationConfig $validationConfig
     * @param SimpleProfile $sessionProfile
     * @return ConcurrentSession
     */
    function createEmptySession(ValidationConfig $validationConfig = null, SimpleProfile $sessionProfile = null) {

        return $this->sessionManager->createSession([]);
    }

    
//    function duplicateSession(ConcurrentSession $session1) {
//        return new RedisSession(
//            $session1->getSessionId(),
//            $this->redisDriver,
//            $this->sessionManager,
//            [], []
//        );
//    }


//    /**
//     * 
//     */
//    function testAsyncValues() {
//        $session1 = $this->createEmptySession();
//        $session2 = $this->duplicateSession($session1);
//
//        $key = 'upload';
//        
//        $session1->increment($key, 1);
//        $session2->increment($key, 1);
//
//        $this->assertEquals(2, $session1->get($key));
//        $this->assertEquals(2, $session2->get($key));
//
//        $session1->increment('upload', -1);
//        $this->assertEquals(1, $session1->get($key));
//        $this->assertEquals(1, $session2->get($key));
//
//        $session2->set($key, 5);
//        $this->assertEquals(5, $session1->get($key));
//        $this->assertEquals(5, $session2->get($key));
//    }


    function disabled_testAsyncList()
    {
        $session1 = $this->createEmptySession();
        $session2 = $this->duplicateSession($session1);

        $values = [
            'aaa',
            'bbb',
            'ccc',
            'ddd',
            'eee',
        ];

        $key = 'testArray';
        $session1->appendToList($key, $values[0]);
        $currentValue = $session2->getList($key);
        $this->assertEquals([$values[0]], $currentValue, "Get List after setting failed.");

        $session1->appendToList($key, [$values[1], $values[2]]);
        $currentValue = $session2->getList($key);
        $expectedValue = [
            $values[0],
            $values[1],
            $values[2],
        ];

        $this->assertEquals($expectedValue, $currentValue, "Get List after appending array failed.");

        $session2->clearList($key);
        $currentValue = $session1->getList($key);
        $this->assertEquals([], $currentValue, "Getting list after clearing failed.");
    }
    
    function testPHPUnitIsAnnoying()
    {
        //avoid No tests found in class error message.
    }
}