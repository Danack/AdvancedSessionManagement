<?php


namespace ASM;


use ASM\Driver;

interface ConcurrentDriver extends Driver
{

    function get($sessionID, $index);

    function set($sessionID, $index, $value);

    function increment($sessionID, $index, $increment);

    function getList($sessionID, $index);

    function appendToList($sessionID, $key, $value);

    function clearList($sessionID, $index);
}