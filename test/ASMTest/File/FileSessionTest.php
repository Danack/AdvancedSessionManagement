<?php


namespace ASMTest\Tests\File;

use ASMTest\Tests\AbstractSessionTest;
use ASM\IdGenerator;


/**
 * Class FileSessionTest
 * 
 */
class FileSessionTest extends AbstractSessionTest
{
    /**
     * @var \Auryn\Injector
     */
    protected $injector;

    static private $randomSubdir = "subdir";
    
    public static function setUpBeforeClass()
    {
        self::$randomSubdir = "subdir".time()."_".rand(1000000, 10000000);
    }
    
    
    protected function setUp() {
        $this->injector = createProvider();
        parent::setUp();
        resetMockFunctions();
    }
    
    protected function tearDown()
    {
        resetMockFunctions();
    }

    /**
     * @return mixed
     */
    public function getDriver(IdGenerator $idGenerator)
    {
        $path = __DIR__."/../../../tmp/sessfiletest/".self::$randomSubdir;
        @mkdir($path, 0755, true);
        return new \ASM\File\FileDriver(
            $path,
            null,
            $idGenerator
        );
    }

    
    
    
    
    
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