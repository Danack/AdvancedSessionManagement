<?php


namespace ASM;


class StandardIDGenerator implements IDGenerator {

    function generateSessionID() {
        //TODO - understand what is required of a real session ID generator.
        return "".rand(100000000, 999999999);
    }
}