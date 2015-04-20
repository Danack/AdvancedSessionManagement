<?php


namespace ASM\Tests;

use org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamDirectory;




class FileDriverTest extends AbstractDriverTest {
    function getDriver() {
        vfsStream::setup('sessionTest');
        $path = vfsStream::url('sessionTest');
        return $this->injector->make('ASM\Driver\FileDriver', [':path' => $path]);
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