<?php

namespace AsmTest\Tests;

use Asm\IdGenerator\RandomLibIdGenerator;
use PHPUnit\Framework\TestCase;

class IDGeneratorTest extends TestCase
{

    /**
     * Basic lock functionality
     */
    function testSerialization(): void
    {
        $idGenerator = new RandomLibIdGenerator();
        $sessionID = $idGenerator->generateSessionId();
        
        $this->assertIsString($sessionID);
        $this->assertTrue(strlen($sessionID) > 8);
    }
}
