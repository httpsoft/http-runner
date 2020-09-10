<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Runner;

use ArgumentCountError;
use Devanych\Di\Container;
use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Runner\Exception\InvalidMiddlewareResolverHandlerException;
use HttpSoft\Runner\MiddlewarePipeline;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Tests\Runner\TestAsset\DummyHandler;
use HttpSoft\Tests\Runner\TestAsset\FirstMiddleware;
use HttpSoft\Tests\Runner\TestAsset\RequestHandler;
use HttpSoft\Tests\Runner\TestAsset\RequestHandlerAutoWiring;
use HttpSoft\Tests\Runner\TestAsset\SecondMiddleware;
use HttpSoft\Tests\Runner\TestAsset\ThirdMiddleware;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_key_exists;

class MiddlewareResolverTest extends TestCase
{
    /**
     * @var MiddlewareResolver
     */
    private MiddlewareResolver $resolver;

    /**
     * @var RequestHandler
     */
    private RequestHandler $handler;

    /**
     * @var ServerRequest
     */
    private ServerRequest $request;

    public function setUp(): void
    {
        $this->resolver = new MiddlewareResolver();
        $this->handler = new RequestHandler();
        $this->request = new ServerRequest();
    }

    /**
     * @return array
     */
    public function validHandlerProvider(): array
    {
        return [
            'middleware-class' => [FirstMiddleware::class],
            'middleware-object' => [new FirstMiddleware()],
            'request-handler-class' => [RequestHandler::class],
            'request-handler-object' => [new RequestHandler()],
            'callable-without-args' => [
                fn(): ResponseInterface => new Response(),
            ],
            'callable-with-signature-as-request-handler-handle' => [
                fn(ServerRequestInterface $request): ResponseInterface => new Response(),
            ],
            'callable-with-signature-as-middleware-process' => [
                function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
                    return $handler->handle($request);
                },
            ],
            'array-callable-without-args' => [
                [new DummyHandler(), 'handler'],
            ],
            'array-static-callable-without-args' => [
                [DummyHandler::class, 'staticHandler'],
            ],
            'array-static-callable-with-args' => [
                [new DummyHandler(), 'process'],
            ],
            'array-middleware-classes' => [
                [
                    FirstMiddleware::class,
                    SecondMiddleware::class,
                    ThirdMiddleware::class,
                ],
            ],
            'array-middleware-request-handle-classes' => [
                [
                    FirstMiddleware::class,
                    SecondMiddleware::class,
                    ThirdMiddleware::class,
                    RequestHandler::class,
                ],
            ],
            'array-middleware-objects' => [
                [
                    new FirstMiddleware(),
                    new SecondMiddleware(),
                    new ThirdMiddleware(),
                ],
            ],
            'array-middleware-request-handle-objects' => [
                [
                    new FirstMiddleware(),
                    new SecondMiddleware(),
                    new ThirdMiddleware(),
                    new RequestHandler(),
                ],
            ],
            'array-middleware-request-handle-classes-objects' => [
                [
                    new FirstMiddleware(),
                    SecondMiddleware::class,
                    new ThirdMiddleware(),
                    RequestHandler::class,
                ],
            ],

            'array-middleware-callable-request-handle-classes-objects' => [
                [
                    FirstMiddleware::class,
                    [new SecondMiddleware(), 'process'],
                    function (ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
                        return (new ThirdMiddleware())->process($request, $handler);
                    },
                    new RequestHandler(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider validHandlerProvider
     * @param mixed $handler
     */
    public function testResolve($handler): void
    {
        $middleware = $this->resolver->resolve($handler);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
        $this->assertInstanceOf(ResponseInterface::class, $middleware->process($this->request, $this->handler));
    }

    /**
     * @return array
     */
    public function invalidHandlerProvider(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'integer' => [1],
            'float' => [1.1],
            'string' => ['string'],
            'empty-array' => [[]],
            'class-not-exist' => ['Class\Not\Exist'],
            'class-not-middleware-request-handler' => [DummyHandler::class],
            'object-not-middleware-request-handler' => [new DummyHandler()],
            'array-item-not-middleware-or-request-handle-classes' => [
                [
                    FirstMiddleware::class,
                    DummyHandler::class,
                    RequestHandler::class,
                ],
            ],
            'array-item-not-middleware-or-request-handle-objects' => [
                [
                    new FirstMiddleware(),
                    new DummyHandler(),
                    new RequestHandler(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidHandlerProvider
     * @param mixed $handler
     */
    public function testResolveThrowExceptionForInvalidHandler($handler): void
    {
        $this->expectException(InvalidMiddlewareResolverHandlerException::class);
        $this->resolver->resolve($handler);
    }

    /**
     * @return array
     */
    public function invalidCallableHandlerProvider(): array
    {
        return [
            'callable-without-args-not-returns-ResponseInterface' => [fn() => null],
            'callable-with-signature-as-request-handler-handle-not-returns-ResponseInterface' => [
                fn(ServerRequestInterface $request) => $request,
            ],
            'callable-with-signature-as-middleware-process-not-returns-ResponseInterface' => [
                function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                    return $request ?: $handler;
                },
            ],
            'array-callable-without-args-not-returns-ResponseInterface' => [
                [new DummyHandler(), 'invalidHandler'],
            ],
            'array-static-callable-without-args-not-returns-ResponseInterface' => [
                [DummyHandler::class, 'invalidStaticHandler'],
            ],
            'array-static-callable-with-args-not-returns-ResponseInterface' => [
                [new DummyHandler(), 'invalidProcess'],
            ],
        ];
    }

    /**
     * @dataProvider invalidCallableHandlerProvider
     * @param callable $handler
     */
    public function testResolveThrowExceptionForInvalidCallableHandler(callable $handler): void
    {
        $middleware = $this->resolver->resolve($handler);
        $this->expectException(InvalidMiddlewareResolverHandlerException::class);
        $middleware->process($this->request, $this->handler);
    }

    public function testResolveByArrayAlwaysReturnsMiddlewarePipeline(): void
    {
        $middleware = $this->resolver->resolve([
            FirstMiddleware::class,
            [new SecondMiddleware(), 'process'],
            fn(): ResponseInterface => new Response(),
            new RequestHandler(),
        ]);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
        $this->assertInstanceOf(MiddlewarePipeline::class, $middleware);
        $this->assertInstanceOf(ResponseInterface::class, $middleware->process($this->request, $this->handler));
    }

    public function testResolveClassNameHandlerWithDependenciesNotPassingContainerToConstructor(): void
    {
        $this->expectException(ArgumentCountError::class);
        $this->resolver->resolve(RequestHandlerAutoWiring::class);
    }

    public function testResolveClassNameHandlerWithDependenciesPassingContainerWithoutAutoWiringToConstructor(): void
    {
        $resolver = new MiddlewareResolver($this->createContainerWithoutAutoWiring());
        $this->expectException(NotFoundExceptionInterface::class);
        $resolver->resolve(RequestHandlerAutoWiring::class);
    }

    public function testResolveClassNameHandlerWithDependenciesPassingContainerWithAutoWiringToConstructor(): void
    {
        $resolver = new MiddlewareResolver(new Container());
        $middleware = $resolver->resolve(RequestHandlerAutoWiring::class);
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
        $this->assertInstanceOf(ResponseInterface::class, $middleware->process($this->request, $this->handler));
    }

    /**
     * @return ContainerInterface
     */
    private function createContainerWithoutAutoWiring(): ContainerInterface
    {
        return new class implements ContainerInterface {
            private array $dependencies = [];

            public function get($id)
            {
                if ($this->has($id)) {
                    return $this->dependencies[$id];
                }

                throw new class extends InvalidArgumentException implements NotFoundExceptionInterface {
                };
            }

            public function has($id)
            {
                return array_key_exists($id, $this->dependencies);
            }
        };
    }
}
