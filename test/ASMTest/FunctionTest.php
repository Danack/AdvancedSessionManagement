<?php

namespace ASMTest\Tests;

use ASM\Asm;

/**
 * Class FunctionTest
 */
class FunctionTest extends \PHPUnit_Framework_TestCase {

    protected function setUp() {
    }

    function testCookieGeneration() {
        ini_set('date.timezone', 'UTC');

        $sessionName = 'TestSession';
        $sessionID = 12345;
        $lifetime  = 1000;

        $tests = array(
[
    'expected' => 'TestSession=12345; expires=Wed, 04 Mar 1998 12:46:40 UTC; Max-Age=1000; httpOnly',
    'params' => array(),
],
[
    'expected' => 'TestSession=12345; expires=Wed, 04 Mar 1998 12:46:40 UTC; Max-Age=1000; path=/; domain=.example.com; httpOnly',
    'params' => array('domain' => '.example.com', 'path' => '/'),
],

[
    'expected' => 'TestSession=12345; expires=Wed, 04 Mar 1998 12:46:40 UTC; Max-Age=1000; path=/; domain=www.example.com; httpOnly',
    'params' => array('domain' => 'www.example.com', 'path' => '/'),
],
        );

        foreach ($tests as $test) {
            $expected = $test['expected'];
            $params = $test['params'];
            $path = null;
            $domain = null;

            if (array_key_exists('path', $params)) {
                $path = $params['path'];
            }

            if (array_key_exists('domain', $params)) {
                $domain = $params['domain'];
            }

            $time = mktime (12, 30, 0, 3, 4, 1998);
            $setCookieString = ASM::generateCookieHeaderString($time, $sessionName, $sessionID, $lifetime, $path, $domain);

            $this->assertEquals($expected, $setCookieString);
        }
    }
}

/*
Set-Cookie: LSID=DQAAAK…Eaem_vYg; Path=/accounts; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly
Set-Cookie: HSID=AYQEVn….DKrdst; Domain=.foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; HttpOnly
Set-Cookie: SSID=Ap4P….GTEq; Domain=foo.com; Path=/; Expires=Wed, 13 Jan 2021 22:23:01 GMT; Secure; HttpOnly
*/