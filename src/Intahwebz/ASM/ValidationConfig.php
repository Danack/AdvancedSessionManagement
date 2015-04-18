<?php

namespace Intahwebz\ASM;


class ValidationConfig {

    private $profileChanged;
    private $zombieKeyAccessed;
    private $invalidSessionAccessed;

    function __construct(callable $profileChanged = null,
                         callable $zombieKeyAccessed = null,
                         callable $invalidSessionAccessed = null) {
        $this->profileChanged = $profileChanged;
        $this->zombieKeyAccessed = $zombieKeyAccessed;
        $this->invalidSessionAccessed = $invalidSessionAccessed;
    }

    /**
     * @return callable
     */
    public function getInvalidSessionAccessed() {
        return $this->invalidSessionAccessed;
    }

    /**
     * @return callable
     */
    public function getProfileChangedCallable() {
        return $this->profileChanged;
    }

    /**
     * @return callable
     */
    public function getZombieKeyAccessed() {
        return $this->zombieKeyAccessed;
    }
}

 