<?php



require_once('./vendor/autoload.php');


/**
 * @param array $mocks
 * @param array $shares
 * @return \Auryn\Provider
 */
function createProvider($mocks = array(), $shares = array()) {

    \Intahwebz\ASM\Functions::load();

    $standardImplementations = [
        //'Intahwebz\Session' => Intahwebz\Session\MockSession::class,
    ];

    $provider = new \Auryn\Provider();
    $provider->alias('Psr\Log\LoggerInterface', 'Monolog\Logger');

    foreach ($standardImplementations as $interface => $implementation) {
        if (array_key_exists($interface, $mocks)) {
            if (is_object($mocks[$interface]) == true) {
                $provider->alias($interface, get_class($mocks[$interface]));
                $provider->share($mocks[$interface]);
            }
            else {
                $provider->alias($interface, $mocks[$interface]);
            }
            unset($mocks[$interface]);
        }
        else {
            $provider->alias($interface, $implementation);
        }
    }

    foreach ($mocks as $class => $implementation) {
        if (is_object($implementation) == true) {
            $provider->alias($class, get_class($implementation));
            $provider->share($implementation);
        }
        else {
            $provider->alias($class, $implementation);
        }
    }

    $standardShares = [
    ];

    foreach ($standardShares as $class => $share) {
        if (array_key_exists($class, $shares)) {
            $provider->share($shares[$class]);
            unset($shares[$class]);
        }
        else {
            $provider->share($share);
        }
    }

    foreach ($shares as $class => $share) {
        $provider->share($share);
    }

    
    $provider->share($provider); //Yolo ServiceLocator

    return $provider;
}

 