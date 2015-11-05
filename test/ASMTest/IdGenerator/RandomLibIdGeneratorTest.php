<?php

namespace ASMTest\Tests;



use ASM\IdGenerator\RandomLibIdGenerator;

class IDGeneratorTest extends \PHPUnit_Framework_TestCase {

    /**
     * Basic lock functionality
     */
    function testSerialization() {        
        $idGenerator = new RandomLibIdGenerator();
        $sessionID = $idGenerator->generateSessionId();
        
        $this->assertInternalType('string', $sessionID);
        $this->assertTrue(strlen($sessionID) > 8);
    }
}




 