<?php

namespace ASM\Tests;


use \ASM\StandardIDGenerator;

class IDGeneratorTest extends \PHPUnit_Framework_TestCase {

    /**
     * Basic lock functionality
     */
    function testSerialization() {        
        $idGenerator = new StandardIDGenerator();
        $sessionID = $idGenerator->generateSessionID();
        
        $this->assertInternalType('string', $sessionID);
        $this->assertTrue(strlen($sessionID) > 8);
    }
}




 