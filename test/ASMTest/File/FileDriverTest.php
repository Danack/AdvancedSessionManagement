<?php


namespace ASMTest\Tests\File;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;


use ASM\Serializer\PHPSerializer;
use ASM\IdGenerator\RandomLibIdGenerator;
use ASMTest\Tests\AbstractDriverTest;

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

    protected function setUp() {
        $this->injector = createProvider();
    }

    /**
     * @return mixed
     */
    function getDriver()
    {
//        vfsStream::setup('sessionTest');
//        $path = vfsStream::url('sessionTest');
//        // this is showing errors
//        //return $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);
        $path = "./sessfiletest/subdir".rand(1000000, 10000000);
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

        $path = "./sessfiletest/subdir".rand(1000000, 10000000);
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
        $this->setExpectedException('ASM\AsmException');
        $vfsStreamDirectory = vfsStream::newDirectory('sessionTest', 0);        
        $path = $vfsStreamDirectory->url();
        $fileDriver = $this->injector->make('ASM\File\FileDriver', [':path' => $path]);
//        new SessionConfig();
        //$sessionManager = $this->injector->make('ASM\SessionManager', [':sessionName' => 'testUnwriteable']);

        $sessionManager = createSessionManager($fileDriver);
        $fileDriver->createSession($sessionManager);
    }    
}

/*
    vfsStream setup.


     * Assumed $structure contains an array like this:
     * <code>
     * array('Core' = array('AbstractFactory' => array('test.php'    => 'some text content',
     *                                                 'other.php'   => 'Some more text content',
     *                                                 'Invalid.csv' => 'Something else',
     *                                           ),
     *                      'AnEmptyFolder'   => array(),
     *                      'badlocation.php' => 'some bad content',
     *                )
     * )
     * </code>
     * the resulting directory tree will look like this:
     * <pre>
     * root
     * \- Core
     *  |- badlocation.php
     *  |- AbstractFactory
     *  | |- test.php
     *  | |- other.php
     *  | \- Invalid.csv
     *  \- AnEmptyFolder


*/