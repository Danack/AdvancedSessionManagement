<?php


namespace ASM\Tests;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

use ASM\Mock\MockSessionManager;

class FileDriverTest extends AbstractDriverTest {
    function getDriver() {
//        vfsStream::setup('sessionTest');
//        $path = vfsStream::url('sessionTest');
//        // this is showing errors
//        //return $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);

        $path = "./sessfiletest/subdir".rand(1000000, 10000000);
        @mkdir($path, true);

        return $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);
    }
    
    
    function testEmptyDirNotAcceptable()
    {
        $this->setExpectedException('ASM\AsmException');
        $this->injector->make('ASM\Driver\FileDriver', [':path' => ""]);
    }

    function testCreate()
    {
        parent::testCreate();
    }

    /**
     * This just covers a few lines in the constructor of ASM\Driver\FileDriver
     * @throws \Auryn\InjectorException
     */
    function testCoverage()
    {
        $serializer = new \ASM\PHPSerializer();
        $idGenerator = new \ASM\StandardIDGenerator();

        $path = "./sessfiletest/subdir".rand(1000000, 10000000);
        @mkdir($path, true);

        $this->injector->alias('ASM\Serializer', get_class($serializer));
        $this->injector->share($serializer);

        $this->injector->alias('ASM\IDGenerator', get_class($idGenerator));
        $this->injector->share($idGenerator);

        $fileDriver = new \ASM\Driver\FileDriver($path, $serializer, $idGenerator);
    }
    
    function testUnwriteable()
    {
        $this->setExpectedException('ASM\AsmException');
        $vfsStreamDirectory = vfsStream::newDirectory('sessionTest', 0);        
        $path = $vfsStreamDirectory->url();
        $fileDriver = $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);
        $fileDriver->createSession(new MockSessionManager());
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