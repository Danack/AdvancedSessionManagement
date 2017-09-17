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
        // todo - read header, not cookie
        $session = $this->sessionManager->createSession($_COOKIE);



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
