<?php

namespace Asm;

use Asm\Session;


/**
 * Allows an active session to be stored back from the Asm middleware
 * so it can be accessed by your application.
 */
interface RequestSessionStorage
{
    public function store(Session $session);

    public function get(): Session|null;
}