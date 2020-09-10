<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Runner\TestAsset;

use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use StdClass;

class DummyHandler
{
    /**
     * @return Response
     */
    public static function staticHandler()
    {
        return new Response();
    }

    /**
     * @return null
     */
    public static function invalidStaticHandler()
    {
        return new StdClass();
    }

    /**
     * @return Response
     */
    public function handler()
    {
        return new Response();
    }

    /**
     * @return null
     */
    public function invalidHandler()
    {
        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return bool
     */
    public function invalidProcess(ServerRequestInterface $request, RequestHandlerInterface $handler): bool
    {
        return false;
    }
}
