<?php

namespace AsmTest\Tests;

use Asm\Serializer\PHPSerializer;
use Asm\Serializer\JsonSerializer;
use PHPUnit\Framework\TestCase;

/**
 * Class SerializerTest
 *
 */
class SerializerTest extends TestCase
{

    /**
     * Basic Serialization functionality
     *
     */
    function testPHPSerializer()
    {
        $serializer = new PHPSerializer();
        
        $tests = [
            ['foo'],
            ['foo' => 'bar'],
            ['foo' => new \StdClass()]
        ];
        
        foreach ($tests as $test) {
            $string = $serializer->serialize($test);
            $result = $serializer->unserialize($string);
            $this->assertEquals($test, $result);
        }
    }
    
        /**
     * Basic Serialization functionality
     *
     */
    function testJSONSerializer()
    {
        $serializer = new JsonSerializer();

        $tests = [
            ['foo'],
            ['foo' => 'bar'],
            //['foo' => new \StdClass()]
        ];
        
        foreach ($tests as $test) {
            $string = $serializer->serialize($test);
            $result = $serializer->unserialize($string);
            $this->assertEquals($test, $result);
        }
    }
}
