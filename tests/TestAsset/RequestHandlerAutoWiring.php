<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Runner\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerAutoWiring implements RequestHandlerInterface
{
    /**
     * @var DummyHandler
     */
    private DummyHandler $dummyHandler;

    /**
     * @param DummyHandler $dummyHandler
     */
    public function __construct(DummyHandler $dummyHandler)
    {
        $this->dummyHandler = $dummyHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->dummyHandler->handler();
    }
}
