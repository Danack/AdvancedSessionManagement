<?php


namespace ASM\IdGenerator;

use RandomLib\Factory;
use ASM\IDGenerator;


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

    function generateSessionID()
    {
        // We use a restricted set of characters to allow simplifications 
        // in the session driver implementations.
        return $this->generator->generateString(
            10,
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        );
    }
}