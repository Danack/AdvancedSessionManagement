<?php

class FunctionTest extends \PHPUnit_Framework_TestCase {

    protected function setUp() {
        //$provider = createProvider();
        \Intahwebz\ASM\Functions::load();
    }


    function testCookieGeneration() {
        ini_set('date.timezone', 'UTC');

        $sessionName = 'TestSession';
        $sessionID = 12345;
        $lifetime  = 1000;

        $tests = array(
            'Set-Cookie: TestSession=12345; expires=Wed, 04-Mar-1998 12:46:40 UTC; Max-Age=1000; httpOnly' => array('domain' => '.example.com', 'path' => '/'),
        );

        foreach ($tests as $expected => $params) {
            $path = null;
            $domain = null;

            $time = mktime (12, 30, 0, 3, 4, 1998);
            $result = generateCookieHeader($time, $sessionName, $sessionID, $lifetime, $path, $domain);
            $this->assertEquals($expected, $result);
        }
    }
}




 