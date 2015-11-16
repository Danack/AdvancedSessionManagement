<?php

namespace ASMTest\Stub;

use ASM\IdGenerator;

class XKCDIDGenerator implements IdGenerator
{
    function generateSessionId()
    {
        return "4";
    }
}

