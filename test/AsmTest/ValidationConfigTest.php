<?php



namespace ASMTest;

use Asm\SessionManager;
use Asm\ValidationConfig;
use PHPUnit\Framework\TestCase;

/**
 * Class ValidationConfigTest
 */
class ValidationConfigTest extends TestCase
{

    function testBasic()
    {
        $profileChanged = function (SessionManager $session, $userProfile, $sessionProfiles) {
        };
        $zombieKeyAccessed = function (SessionManager $session) {
        };
        $invalidSessionAccessed = function (SessionManager $session) {
        };
        $lostLockCallable = function (SessionManager $session) {
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
