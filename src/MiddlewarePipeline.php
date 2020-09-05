<?php

declare(strict_types=1);

namespace HttpSoft\Runner;

use HttpSoft\Runner\Exception\EmptyMiddlewarePipelineException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;
use function stripos;
use function trim;

final class MiddlewarePipeline implements MiddlewarePipelineInterface
{
    /**
     * @var MiddlewareInterface[]
     */
    private array $pipeline = [];

    /**
     * {@inheritDoc}
     */
    public function pipe(MiddlewareInterface $middleware, string $pathPrefix = null): void
    {
        $this->pipeline[] = (!$pathPrefix || $pathPrefix === '/') ? $middleware : $this->path($pathPrefix, $middleware);
    }

    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw EmptyMiddlewarePipelineException::create(MiddlewarePipeline::class);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->next($handler)->handle($request);
    }

    /**
     * @param RequestHandlerInterface $defaultHandler
     * @return RequestHandlerInterface
     */
    private function next(RequestHandlerInterface $defaultHandler): RequestHandlerInterface
    {
        return new class ($this->pipeline, $defaultHandler) implements RequestHandlerInterface {
            private RequestHandlerInterface $handler;
            /** @var MiddlewareInterface[] */
            private array $pipeline;

            public function __construct(array $pipeline, RequestHandlerInterface $handler)
            {
                $this->handler = $handler;
                /** @var MiddlewareInterface[] $pipeline */
                $this->pipeline = $pipeline;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if (!$middleware = array_shift($this->pipeline)) {
                    return $this->handler->handle($request);
                }

                $next = clone $this;
                $this->pipeline = [];
                return $middleware->process($request, $next);
            }
        };
    }

    /**
     * @param string $prefix
     * @param MiddlewareInterface $middleware
     * @return MiddlewareInterface
     */
    private function path(string $prefix, MiddlewareInterface $middleware): MiddlewareInterface
    {
        return new class ($prefix, $middleware) implements MiddlewareInterface {
            private MiddlewareInterface $middleware;
            private string $prefix;

            public function __construct(string $prefix, MiddlewareInterface $middleware)
            {
                $this->prefix = $this->normalize($prefix);
                $this->middleware = $middleware;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $path = $this->normalize($request->getUri()->getPath());

                if ($this->prefix === '/' || stripos($path, $this->prefix) === 0) {
                    return $this->middleware->process($request, $handler);
                }

                return $handler->handle($request);
            }

            private function normalize(string $path): string
            {
                $path = '/' . trim($path, '/');
                return ($path === '/') ? '/' : $path . '/';
            }
        };
    }
}
