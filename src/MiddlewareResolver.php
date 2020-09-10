<?php

declare(strict_types=1);

namespace HttpSoft\Runner;

use HttpSoft\Runner\Exception\InvalidMiddlewareResolverHandlerException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function class_exists;
use function is_array;
use function is_callable;
use function is_string;

final class MiddlewareResolver implements MiddlewareResolverInterface
{
    /**
     * @var ContainerInterface|null
     */
    private ?ContainerInterface $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     *
     * The handler must be one of:
     *
     * - a class name (string) or an object that implements `MiddlewareInterface` or `RequestHandlerInterface`;
     * - a callable without arguments that returns an instance of `ResponseInterface`;
     * - a callable matching signature of `MiddlewareInterface::process()`;
     * - an array of previously listed handlers.
     *
     * @throws InvalidMiddlewareResolverHandlerException if the handler is not valid.
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedMethodCall
     */
    public function resolve($handler): MiddlewareInterface
    {
        if (is_string($handler) && class_exists($handler)) {
            $handler = $this->container ? $this->container->get($handler) : new $handler();
        }

        if ($handler instanceof MiddlewareInterface) {
            return $handler;
        }

        if ($handler instanceof RequestHandlerInterface) {
            return $this->handler($handler);
        }

        if (is_callable($handler)) {
            return $this->callable($handler);
        }

        if (is_array($handler) && $handler !== []) {
            return $this->array($handler);
        }

        throw InvalidMiddlewareResolverHandlerException::create($handler);
    }

    /**
     * @param array $handlers
     * @return MiddlewareInterface
     * @psalm-suppress MixedAssignment
     */
    private function array(array $handlers): MiddlewareInterface
    {
        $pipeline = new MiddlewarePipeline();

        foreach ($handlers as $handler) {
            $pipeline->pipe($this->resolve($handler));
        }

        return $pipeline;
    }

    /**
     * @param callable $handler
     * @return MiddlewareInterface
     * @throws InvalidMiddlewareResolverHandlerException if the handler does not return a `ResponseInterface` instance.
     * @psalm-suppress MixedAssignment
     */
    private function callable(callable $handler): MiddlewareInterface
    {
        return new class ($handler) implements MiddlewareInterface {
            private $callable;

            public function __construct(callable $callable)
            {
                $this->callable = $callable;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                $response = ($this->callable)($request, $handler);

                if (!($response instanceof ResponseInterface)) {
                    throw InvalidMiddlewareResolverHandlerException::forCallableMissingResponse($response);
                }

                return $response;
            }
        };
    }

    /**
     * @param RequestHandlerInterface $handler
     * @return MiddlewareInterface
     */
    private function handler(RequestHandlerInterface $handler): MiddlewareInterface
    {
        return new class ($handler) implements MiddlewareInterface {
            private RequestHandlerInterface $handler;

            public function __construct(RequestHandlerInterface $handler)
            {
                $this->handler = $handler;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $this->handler->handle($request);
            }
        };
    }
}
