<?php


namespace ASM;



interface ConcurrentDriver extends Driver
{
    function get($sessionId, $index);

    function set($sessionId, $index, $value);

    function increment($sessionId, $index, $increment);

    function getList($sessionId, $index);

    function appendToList($sessionId, $key, $value);

    function clearList($sessionId, $index);
}

