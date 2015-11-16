<?php


namespace ASMTest\Tests\File;

use org\bovigo\vfs\vfsStream;
use ASM\Serializer\PHPSerializer;
use ASM\IdGenerator\RandomLibIdGenerator;
use ASMTest\Tests\AbstractDriverTest;
use ASM\File\FileInfo;
use ASM\AsmException;

/**
 * Class FileDriverTest
 * 
 */
class FileDriverTest extends AbstractDriverTest
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
        resetMockFunctions();
    }
    
    protected function tearDown()
    {
        resetMockFunctions();
    }

    /**
     * @return mixed
     */
    function getDriver()
    {
        // vfsStream does not implement inodes. The File session Driver depends on 
        // inodes to implement locking, and so it cannot be used for testing.
        $path = __DIR__."/../../../tmp/sessfiletest/subdir".self::$randomSubdir;
        @mkdir($path, 0755, true);

        return $this->injector->make('ASM\File\FileDriver', [':path' => $path]);
    }

    /**
     *
     */
    function testEmptyDirNotAcceptable()
    {
        $this->setExpectedException('ASM\AsmException');
        $this->injector->make('ASM\File\FileDriver', [':path' => ""]);
    }


    /**
     * This test just covers a few lines in the constructor of ASM\Driver\FileDriver
     * it has no behaviour to test.
     *
     */
    function testCoverage()
    {
        $serializer = new PHPSerializer();
        $idGenerator = new RandomLibIdGenerator();

        $path = __DIR__."./../../../tmp/sessfiletest/subdir".self::$randomSubdir;
        @mkdir($path, 0755, true);

        $this->injector->alias('ASM\Serializer', get_class($serializer));
        $this->injector->share($serializer);

        $this->injector->alias('ASM\IDGenerator', get_class($idGenerator));
        $this->injector->share($idGenerator);

        $fileDriver = new \ASM\File\FileDriver($path, $serializer, $idGenerator);
    }

    /**
     *
     */
    function testUnwriteable()
    {
        $fileDriver = $this->getDriver();
        $sessionManager = createSessionManager($fileDriver);
        $fn = function () {
            return false;
        };
        mock('fopen', $fn);
        $this->setExpectedException('ASM\AsmException');
        $fileDriver->createSession($sessionManager);
    }
    
    
    /**
     *
     */
    function testFilePutContentsFail()
    {
        $vfsStreamDirectory = vfsStream::newDirectory('sessionTestVFS', 0);        
        $path = $vfsStreamDirectory->url();
        $fileDriver = $this->injector->make('ASM\File\FileDriver', [':path' => $path]);
        $fileInfo = new FileInfo(null);

        mock('file_put_contents', function() {return false;});

        $this->setExpectedException('ASM\AsmException');
        $fileDriver->save(
            12345,
            ['foo' => 'bar'],
            $existingProfiles = [],
            $fileInfo
        );
    }
    
    function testRenameFail()
    {
        $fileDriver = $this->getDriver();
        
        $fileInfo = new FileInfo(null);
        mock('rename', function() {return false;});
        $this->setExpectedException(
            'ASM\AsmException',
            '',
            AsmException::IO_ERROR
        );
        $fileDriver->save(
            12345,
            ['foo' => 'bar'],
            $existingProfiles = [],
            $fileInfo
        );
    }


    function testNoLockValidatesAsNoLock()
    {
        $fileDriver = $this->getDriver();
        $fileInfo = new FileInfo(null);
        $result = $fileDriver->validateLock(12345, $fileInfo);
        $this->assertFalse($result);
    }
    
    
   function testValidatesLock_failsDueToMissingMtime()
   {
       $fileDriver = $this->getDriver();

       $statFn = function() {
           return ['ino' => 12345];
       };
       mock('stat', $statFn);
       mock('fstat', $statFn);

       $lockFileHandle = $fileDriver->acquireLock(12345, 10000, 1000);
       $fileInfo = new FileInfo($lockFileHandle);

       $this->setExpectedException(
           'ASM\AsmException',
           '',
           AsmException::IO_ERROR
       );
       
       $result = $fileDriver->validateLock(12345, $fileInfo);
   }
    

    function testValidatesLock_failsDueToStatNoIno()
    {
        $fileDriver = $this->getDriver();

        $statFn = function() {
            return [];
        };
        mock('stat', $statFn);
        
        $lockFileHandle = $fileDriver->acquireLock(12345, 10000, 1000);
        $fileInfo = new FileInfo($lockFileHandle);
        
        $result = $fileDriver->validateLock(12345, $fileInfo);
        $this->assertFalse($result);
    }
    

    function testValidatesLock_failsDueToFstatNoIno()
    {
        $fileDriver = $this->getDriver();
        
        $statFn = function() {
            return [];
        };

        mock('fstat', $statFn);
        
        $lockFileHandle = $fileDriver->acquireLock(12345, 10000, 1000);
        $fileInfo = new FileInfo($lockFileHandle);
        
        $result = $fileDriver->validateLock(12345, $fileInfo);
        $this->assertFalse($result);
    }


    function testValidatesLock_failsDueToNotValid()
    {
        $fileDriver = $this->getDriver();
        
        $lockFileHandle1 = $fileDriver->acquireLock(12345, 10000, 1000);
        $fileInfo1 = new FileInfo($lockFileHandle1);
        $fileDriver->forceReleaseLockByID(12345);
        //Test that the previous lock is valid should fail.
        $result = $fileDriver->validateLock(12345, $fileInfo1);
        $this->assertFalse($result);
    }
    
}