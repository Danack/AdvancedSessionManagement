<?php


namespace ASM\IdGenerator;

use RandomLib\Factory;
use ASM\IdGenerator;


class RandomLibIdGenerator implements IdGenerator
{

    /**
     * @var \RandomLib\Generator
     */
    private $generator;

    function __construct()
    {
        $factory = new Factory;
        $this->generator = $factory->getMediumStrengthGenerator();
    }

    function generateSessionId()
    {
        // We use a restricted set of characters to allow simplifications 
        // in the session driver implementations.
        return $this->generator->generateString(
            10,
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        );
    }
}