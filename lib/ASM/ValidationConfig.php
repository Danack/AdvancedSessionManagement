<?php

namespace ASM;


class ValidationConfig
{

    /**
     * @var callable Which callable to call when the profile has changed. The callable
     * is passed both the profile as it was when the session was generated, and the new profile
     */
    private $profileChanged;

    /**
     * @var callable Which callable to call when a zombie session has been accessed.
     */
    private $zombieKeyAccessed;

    /**
     * @var callable Which callable to call when a totally invalid session has been accessed.
     */
    private $invalidSessionAccessed;


    /**
     * @var callable Which callable to call when a lock has been lost. If this callable
     * is not set
     */
    private $lostLockCallable;

    function __construct(
        callable $profileChanged = null,
        callable $zombieKeyAccessed = null,
        callable $invalidSessionAccessed = null,
        callable $lostLockCallable = null
    ) {
        $this->profileChanged = $profileChanged;
        $this->zombieKeyAccessed = $zombieKeyAccessed;
        $this->invalidSessionAccessed = $invalidSessionAccessed;
        $this->lostLockCallable = $lostLockCallable;
    }

    /**
     * @return callable
     */
    public function getInvalidSessionAccessedCallable()
    {
        return $this->invalidSessionAccessed;
    }

    /**
     * @return callable
     */
    public function getProfileChangedCallable()
    {
        return $this->profileChanged;
    }

    /**
     * @return callable
     */
    public function getZombieKeyAccessedCallable()
    {
        return $this->zombieKeyAccessed;
    }

    /**
     * @return callable
     */
    public function getLockWasForceReleasedCallable()
    {
        return $this->lostLockCallable;
    }
}

 