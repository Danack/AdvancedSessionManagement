<?php

namespace Asm;

interface IdGenerator
{
    public function generateSessionId(): string;
}
