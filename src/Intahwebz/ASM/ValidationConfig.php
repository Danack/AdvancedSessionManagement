<?php


class ValidationConfig {

    private $useragentChanged;
    private $ipChanged;
    private $invalidSession;

    function __construct(callable $useragentChanged = null,
                         callable $ipChanged = null,
                         callable $invalidSession = null) {
        $this->useragentChanged = $useragentChanged;
        $this->ipChanged = $ipChanged;
        $this->invalidSession = $invalidSession;
    }
}

 