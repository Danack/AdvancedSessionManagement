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
        $session = $this->sessionManager->openSessionFromCookie($request);

        if ($session) {
            $this->requestSessionStorage->store($session);
        }

        $response = $handler->handle($request);

        // Session could have been opened inside request
        $session = $this->requestSessionStorage->get();
        
        if ($session) {
            $session->save();
            $headersArrays = $session->getHeaders(
                \Asm\SessionManager::CACHE_PRIVATE,
                '/'
            //        $domain = false,
            //        $secure = false,
            //        $httpOnly = true
            );

            foreach ($headersArrays as $nameAndValue) {
                $name = $nameAndValue[0];
                $value = $nameAndValue[1];

                /** @var ResponseInterface $response */
                $response = $response->withAddedHeader($name, $value);
            }
        }

        return $response;
    }
}
