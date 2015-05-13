<?php

namespace ASM\Tests;

use ASM\Redis\APCDriver;


class APCDriverTest extends AbstractDriverTest {
    function getDriver() {
        return new APCDriver();
    }
}