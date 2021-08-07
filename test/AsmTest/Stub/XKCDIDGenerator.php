<?php

namespace AsmTest\Stub;

use Asm\IdGenerator;

class XKCDIDGenerator implements IdGenerator
{
    function generateSessionId()
    {
        return "4";
    }
}
