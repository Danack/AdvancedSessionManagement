<?php



namespace ASMTest\Tests;

use ASM\SessionManager;

use ASM\ValidationConfig;

/**
 * Class ValidationConfigTest
 */
class ValidationConfigTest extends \PHPUnit_Framework_TestCase {

    function testBasic()
    {
        $profileChanged = function($this, $userProfile, $sessionProfiles) {
        };
        $zombieKeyAccessed = function(SessionManager $session) {
        };
        $invalidSessionAccessed = function(SessionManager $session) {
        };
        $lostLockCallable = function(SessionManager $session) {
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

