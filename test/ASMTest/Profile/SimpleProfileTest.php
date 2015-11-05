<?php



namespace ASMTest\Tests;

use ASM\Profile\SimpleProfile;

class SimpleProfileTest extends \PHPUnit_Framework_TestCase {

    
    function testBasic()
    {
        $userAgent = 'TestUserAgent';
        $ipAddress = '1.2.3.4';

        $profile1 = new SimpleProfile($userAgent, $ipAddress);
        $profile2 = new SimpleProfile($userAgent, $ipAddress);

        $profileDifferentIP = new SimpleProfile($userAgent, "4.4.4.4");
        $profileDifferentUA = new SimpleProfile("foo", $ipAddress);
        
        $this->assertEquals($profile1->__toString(), $profile2->__toString());
        $this->assertNotEquals($profile1->__toString(), $profileDifferentIP->__toString());
        $this->assertNotEquals($profile1->__toString(), $profileDifferentUA->__toString());

        $this->assertEquals($userAgent, $profile1->getUserAgent());
        $this->assertEquals($ipAddress, $profile1->getIPAddress());
    }
}

