<?php


namespace ASMTest\Tests;

use ASM\Redis\RedisSession;
use ASM\ConcurrentSessionManager;
use ASM\SessionConfig;
use ASM\Profile\SimpleProfile;
use ASM\ValidationConfig;
use Predis\Client as RedisClient;
use ASM\ConcurrentSession;
use ASM\IdGenerator;


abstract class AbstractSessionTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var \ASM\ConcurrentSessionManager
     */
    private $sessionManager;
    
    /**
     * @var \ASM\Driver
     */
    private $driver;

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
        $this->driver = $this->getDriver($idGenerator);

        $this->sessionManager = new ConcurrentSessionManager(
            $sessionConfig,
            $this->driver
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

    
    function duplicateSession(ConcurrentSession $session1) {
        return new RedisSession(
            $session1->getSessionId(),
            $this->driver,
            $this->sessionManager,
            [], []
        );
    }



}