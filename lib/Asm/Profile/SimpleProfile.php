<?php

namespace Asm\Profile;

use function JsonSafe\json_encode_safe;

class SimpleProfile
{
    private string $ipAddress;

    private string $userAgent;

    public function __construct(string $userAgent, string $ipAddress)
    {
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }


    public function getIPAddress(): string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function __toString(): string
    {
        $data = [];
        $data['ipAddress'] = $this->ipAddress;
        $data['userAgent'] = $this->userAgent;

        return json_encode_safe($data);
    }
}
