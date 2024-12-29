<?php

declare(strict_types=1);

namespace HttpSoft\Runner;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewarePipelineInterface extends MiddlewareInterface, RequestHandlerInterface
{
    /**
     * Adds middleware to the pipeline.
     *
     * By specifying the path prefix, the middleware is attached to the specific path
     * and will only be processed if the request URI path starts with the given prefix.
     *
     * One middleware can be attached to different paths:
     *
     * ```php
     * $pipeline->pipe($authMiddleware, '/blog');
     * $pipeline->pipe($authMiddleware, '/forum');
     * ```
     *
     * Multiple intermediate programs can be connected to the same path:
     *
     * ```php
     * $pipeline->pipe($authMiddleware, '/admin');
     * $pipeline->pipe($adminMiddleware, '/admin');
     * ```
     *
     * The path prefix MUST start at the root, and the leading and
     * trailing slashes are optional ("/path" or "path" or "path/").
     *
     * ```php
     * // Available: `https://example.com` and `https://example.com/any`:
     * $pipeline->pipe($commonMiddleware);
     * // Or
     * $pipeline->pipe($commonMiddleware, '');
     * // Or
     * $pipeline->pipe($commonMiddleware, '/');
     *
     * // Available: `https://example.com/api` and `https://example.com/api/any`:
     * $pipeline->pipe($apiMiddleware, '/api');
     *
     * // Available: `https://example.com/api/admin` and `https://example.com/api/admin/any`:
     * $pipeline->pipe($apiAdminMiddleware, '/api/admin');
     * // but not available: `https://example.com/api` and `https://example.com/api/any`.
     * ```
     *
     * @param MiddlewareInterface $middleware
     * @param string|null $pathPrefix path prefix from the root to which the middleware is attached.
     */
    public function pipe(MiddlewareInterface $middleware, ?string $pathPrefix = null): void;
}
