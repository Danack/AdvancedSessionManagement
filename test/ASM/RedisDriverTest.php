<?php

//use ASM\Driver\RedisDriver;
//use Predis\Client as RedisClient;

class RedisDriverTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var \Auryn\Provider
     */
    private $injector = null;
    
    protected function setUp() {
        $this->injector = createProvider();
    }

    function testLock() {
        $sessionID = 12345;
        $injector = $this->injector;
        $redisDriver = $injector->make('ASM\Driver\RedisDriver');
        $this->assertFalse($redisDriver->isLocked($sessionID));
        $redisDriver->acquireLock($sessionID, 1000);
        $this->assertTrue($redisDriver->isLocked($sessionID), "Driver doesn't think lock is acquired.");
        $this->assertTrue($redisDriver->validateLock($sessionID), "Validating lock failed.");
        $redisDriver->releaseLock($sessionID);
        $this->assertFalse($redisDriver->isLocked($sessionID), "Driver thinks lock is still acquired.");
    }


    
    

//    function renewLock($milliseconds);
//
//    function releaseLock();
//

//    function forceReleaseLock($sessionID);
//
//    function findSessionIDFromZombieID($sessionID);
//
//    function setupZombieID($dyingSessionID, $newSessionID, $zombieTimeMilliseconds);
//
//    function save($sessionID, $saveData);
//
//    function destroyExpiredSessions();
//
//    function deleteSession($sessionID);
//
//    function addProfile($sessionID, $sessionProfile);
//
//    function getStoredProfile($sessionID);
//
//    function storeSessionProfiles($sessionProfiles);
//    
    
    
}




 