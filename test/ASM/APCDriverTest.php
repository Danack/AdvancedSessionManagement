<?php

namespace ASM\Tests;

use ASM\APC\APCDriver;


class APCDriverTest extends AbstractDriverTest {


    protected function setUp() {

        if (\isAPCAvailable() == false) {
            $this->markTestSkipped("APC unavailable");
        }
        parent::setUp();

    }

    function getDriver() {
        return new APCDriver();
    }
}