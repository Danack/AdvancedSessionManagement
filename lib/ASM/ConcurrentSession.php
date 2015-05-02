<?php


namespace ASM;



interface ConcurrentSession extends Session
{

    function get($index);

    function set($index, $value);

    function increment($index, $increment);

    function getList($index);

    function appendToList($key, $value);

    function clearList($index);
}