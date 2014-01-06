<?php

class FunctionTest extends \PHPUnit_Framework_TestCase {

    protected function setUp() {
        //$provider = createProvider();
    }


    function testCookieGeneration() {

        $sessionName = 'TestSession';
        $sessionID = 12345;
        $lifetime  = 1000;

        $tests = array(
            'Set-Cookie: TestSession=12345; expires=Tue, 07-Jan-2014 02:15:10 EST; Max-Age=1000; httpOnly' => array('domain' => '.example.com', 'path' => '/'),
        );

        foreach ($tests as $expected => $params) {
            $path = null;
            $domain = null;
        
            $result = generateCookieHeader($sessionName, $sessionID, $lifetime, $path, $domain);
            
            $this->assertEquals($expected, $result);
        }
    }
}




 