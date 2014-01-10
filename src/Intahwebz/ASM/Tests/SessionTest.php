<?php


namespace Intahwebz\ASM\Tests;

use Intahwebz\ASM\SessionConfig;


class SessionTest extends \PHPUnit_Framework_TestCase {


    /**
     * @var \Auryn\Provider
     */
    private $provider;
    
    protected function setUp() {
        $this->provider = createProvider();

        $sessionConfig = new SessionConfig(
            'SessionTest',
            array(
                "scheme" => "tcp",
                "host" => '127.0.0.1',
                "port" => 6379
            ),
            1000,
            10
        );

        $this->provider->share($sessionConfig);
        
        
    }
    
    
    function testLock() {
        
        $mockCookie = array();
        
        $session = $this->provider->make()
            
            Session($sessionConfig, Session::READ_ONLY, $_COOKIE);
    }

}

 