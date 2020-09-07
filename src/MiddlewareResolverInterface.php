<?php

declare(strict_types=1);

namespace HttpSoft\Runner;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareResolverInterface
{
    /**
     * Resolves the request handler by converting it to middleware.
     *
     * If the handler cannot be resolved or is invalid, an exception may be thrown.
     *
     * @param mixed $handler
     * @return MiddlewareInterface
     */
    public function resolve($handler): MiddlewareInterface;
}
