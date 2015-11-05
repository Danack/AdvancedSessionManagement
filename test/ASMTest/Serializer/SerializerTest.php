<?php

namespace ASMTest\Tests;


use ASM\Serializer\PHPSerializer;
use ASM\Serializer\JsonSerializer;

/**
 * Class SerializerTest
 *
 */
class SerializerTest extends \PHPUnit_Framework_TestCase {

    /**
     * Basic Serialization functionality
     * 
     */
    function testPHPSerializer() {        
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




 