<?php


namespace ASM;

/**
 * Class AsmException
 * The base exception class for all exceptions that are thrown by this library.
 * @package ASM
 */
class AsmException extends \Exception
{
    const IO_ERROR = 1;
    const BAD_ARGUMENT = 2;
    const ID_CLASH = 3;
}

