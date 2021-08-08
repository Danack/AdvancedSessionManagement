<?php

declare(strict_types = 1);

namespace Asm\Bridge;

use Auryn\Injector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Asm\Session;
use Asm\SessionManager;

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

    /**
     * @param ServerRequest $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Auryn\ConfigException
     */
    public function __invoke(ServerRequest $request, ResponseInterface $response, $next): ResponseInterface
    {
        $session = $this->sessionManager->createSession($request);
        $this->injector->share($session);
        $this->injector->alias(\Asm\Session::class, get_class($session));

        $response = $next($request, $response);
        $session->save();
        $headers = $session->getHeaders(
            \Asm\SessionManager::CACHE_PRIVATE,
            '/'
            //        $domain = false,
            //        $secure = false,
            //        $httpOnly = true
        );

        foreach ($headers as $key => $value) {
            /** @var ResponseInterface $response */
            $response = $response->withAddedHeader($key, $value);
        }

        return $response;
    }
}
