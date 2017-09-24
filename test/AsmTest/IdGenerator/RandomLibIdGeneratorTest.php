<?php

namespace AsmTest\Tests;

use Asm\IdGenerator\RandomLibIdGenerator;
use PHPUnit\Framework\TestCase;


class IDGeneratorTest extends TestCase {

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




 