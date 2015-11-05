<?php

namespace ASMTest\Tests;

use ASM\APC\APCDriver;


class APCDriverTest extends AbstractDriverTest {
    protected function setUp() {

        $this->markTestSkipped("APC not safe currently.");
        return;
        
        if (\isAPCAvailable() == false) {
            $this->markTestSkipped("APC unavailable");
        }
        parent::setUp();

    }

    function getDriver() {
        return new APCDriver();
    }
}