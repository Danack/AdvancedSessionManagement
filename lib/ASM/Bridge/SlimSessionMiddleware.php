<?php

declare(strict_types = 1);

namespace ASM\Bridge;

use Auryn\Injector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Asm\Session;
use ASM\SessionManager;

class SlimSessionMiddleware
{
    /** @var SessionManager */
    private $sessionManager;

    /** @var Injector  */
    private $injector;

    public function __construct(SessionManager $sessionManager, Injector $injector)
    {
        $this->sessionManager = $sessionManager;
        $this->injector = $injector;
    }

    public function __invoke(Request $request, ResponseInterface $response, $next)
    {
        //TODO - avoid magic variable.
        $session = $this->sessionManager->createSession($_COOKIE);

        $this->injector->share($session);
        $this->injector->alias(\ASM\Session::class, get_class($session));

        $response = $next($request, $response);
        $session->save();
        $headers = $session->getHeaders(\ASM\SessionManager::CACHE_PRIVATE, '/');

        foreach ($headers as $key => $value) {
            /** @var $response \Psr\Http\Message\ResponseInterface */
            $response = $response->withHeader($key, $value);
        }

        return $response;
    }
}
