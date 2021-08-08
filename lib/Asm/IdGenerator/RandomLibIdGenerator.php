<?php


namespace Asm\IdGenerator;

use RandomLib\Factory;
use Asm\IdGenerator;

class RandomLibIdGenerator implements IdGenerator
{
    /**
     * @var \RandomLib\Generator
     */
    private $generator;

    public function __construct()
    {
        $factory = new Factory;
        $this->generator = $factory->getMediumStrengthGenerator();
    }

    public function generateSessionId(): string
    {
        // We use a restricted set of characters to allow simplifications
        // in the session driver implementations.
        return $this->generator->generateString(
            16,
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
        );
    }
}
