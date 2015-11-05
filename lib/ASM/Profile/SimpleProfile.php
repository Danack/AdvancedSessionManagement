<?php

namespace ASM\Profile;


class SimpleProfile
{
    private $ipAddress;

    private $userAgent;

    function __construct($userAgent, $ipAddress)
    {
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    /**
     * @return mixed
     */
    public function getIPAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @return mixed
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $data = [];
        $data['ipAddress'] = $this->ipAddress;
        $data['userAgent'] = $this->userAgent;

        return json_encode($data);
    }
}
