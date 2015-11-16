<?php


namespace ASMTest\Tests\File;

use ASMTest\Tests\AbstractSessionTest;
use ASM\IdGenerator;


/**
 * Class FileSessionTest
 *
 */
class RedisSessionTest extends AbstractSessionTest
{
    /**
     * @return mixed
     */
    public function getDriver(IdGenerator $idGenerator)
    {
        $redisClient = $this->injector->make('Predis\Client');
        checkClient($redisClient, $this);
        $this->injector->share($idGenerator);
        $this->injector->alias('ASM\IdGenerator', get_class($idGenerator));

        return $this->injector->make('\ASM\Redis\RedisDriver');
    }
    
    
    
//            $this->redisConfig = array(
//            "scheme" => "tcp",
//            "host" => '127.0.0.1',
//            "port" => 6379
//        );
//
//        $this->redisOptions = getRedisOptions();

//    /**
//     *
//     */
//    function testEmptyDirNotAcceptable()
//    {
//        $this->setExpectedException('ASM\AsmException');
//        $this->injector->make('ASM\File\FileDriver', [':path' => ""]);
//    }
//
//
//    /**
//     * This test just covers a few lines in the constructor of ASM\Driver\FileDriver
//     * it has no behaviour to test.
//     *
//     */
//    function testCoverage()
//    {
//        $serializer = new PHPSerializer();
//        $idGenerator = new RandomLibIdGenerator();
//
//        $path = "./sessfiletest/subdir".rand(1000000, 10000000);
//        @mkdir($path, 0755, true);
//
//        $this->injector->alias('ASM\Serializer', get_class($serializer));
//        $this->injector->share($serializer);
//
//        $this->injector->alias('ASM\IDGenerator', get_class($idGenerator));
//        $this->injector->share($idGenerator);
//
//        $fileDriver = new \ASM\File\FileDriver($path, $serializer, $idGenerator);
//    }
//
//    /**
//     *
//     */
//    function testUnwriteable()
//    {
//        
//        $vfsStreamDirectory = vfsStream::newDirectory('sessionTest', 0);        
//        $path = $vfsStreamDirectory->url();
//        $fileDriver = $this->injector->make('ASM\File\FileDriver', [':path' => $path]);
//
//        $sessionManager = createSessionManager($fileDriver);
//        $this->setExpectedException('ASM\AsmException');
//        $fileDriver->createSession($sessionManager);
//    }    
}