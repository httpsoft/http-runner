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
    public function __construct(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     *
     * The handler must be one of:
     *
     * - a string (class name or identifier of a container definition) or an instance
     * that implements `MiddlewareInterface` or `RequestHandlerInterface`;
     * - a callable without arguments that returns an instance of `ResponseInterface`;
     * - a callable matching signature of `MiddlewareInterface::process()`;
     * - an array of previously listed handlers.
     *
     * @throws InvalidMiddlewareResolverHandlerException if the handler is not valid.
     */
    public function resolve($handler): MiddlewareInterface
    {
        if ($handler instanceof MiddlewareInterface) {
            return $handler;
        }

        if ($handler instanceof RequestHandlerInterface) {
            return $this->handler($handler);
        }

        if (is_string($handler)) {
            return $this->string($handler);
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

    /**
     * @param string $handler
     * @return MiddlewareInterface
     * @throws InvalidMiddlewareResolverHandlerException if the handler is not valid.
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedMethodCall
     */
    private function string(string $handler): MiddlewareInterface
    {
        return new class ($handler, $this->container) implements MiddlewareInterface {
            private string $string;
            private ?ContainerInterface $container;

            public function __construct(string $string, ?ContainerInterface $container)
            {
                $this->string = $string;
                $this->container = $container;
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                if (class_exists($this->string) || ($this->container && $this->container->has($this->string))) {
                    $instance = $this->container ? $this->container->get($this->string) : new $this->string();

                    if ($instance instanceof MiddlewareInterface) {
                        return $instance->process($request, $handler);
                    }

                    if ($instance instanceof RequestHandlerInterface) {
                        return $instance->handle($request);
                    }
                }

                throw InvalidMiddlewareResolverHandlerException::forStringNotConvertedToInstance($this->string);
            }
        };
    }
}
