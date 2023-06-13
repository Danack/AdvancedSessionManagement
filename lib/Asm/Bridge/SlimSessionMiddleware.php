<?php

declare(strict_types = 1);

namespace Asm\Bridge;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Asm\SessionManager;
use Asm\RequestSessionStorage;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class SlimSessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SessionManager $sessionManager,
        private RequestSessionStorage $requestSessionStorage
    ) {
    }

    /**
     * @param ServerRequest $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \Auryn\ConfigException
     */

    public function process(Request $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->sessionManager->createSession($request);
        $this->requestSessionStorage->store($session);
//        $response = $next($request, $response);
        $response = $handler->handle($request);

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
