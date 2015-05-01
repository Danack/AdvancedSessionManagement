<?php

namespace ASM\Tests;


use ASM\Serializer\PHPSerializer;

class SerializerTest extends \PHPUnit_Framework_TestCase {

    /**
     * Basic lock functionality
     */
    function testSerialization() {        
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
}




 