<?php



namespace ASM\Tests;

use ASM\Session;
use ASM\SimpleProfile;
use ASM\ValidationConfig;

class ValidationConfigTest extends \PHPUnit_Framework_TestCase {

    function testBasic()
    {
        $profileChanged = function($this, $userProfile, $sessionProfiles) {
        };
        $zombieKeyAccessed = function(Session $session) {
        };
        $invalidSessionAccessed = function(Session $session) {
        };
        $lostLockCallable = function(Session $session) {
        };

        $validationConfig = new ValidationConfig(
            $profileChanged,
            $zombieKeyAccessed,
            $invalidSessionAccessed,
            $lostLockCallable
        );

        $this->assertEquals($profileChanged, $validationConfig->getProfileChangedCallable());
        $this->assertEquals($zombieKeyAccessed, $validationConfig->getZombieKeyAccessedCallable());
        $this->assertEquals($invalidSessionAccessed, $validationConfig->getInvalidSessionAccessedCallable());
        $this->assertEquals($lostLockCallable, $validationConfig->getLockWasForceReleasedCallable());
    }
}

