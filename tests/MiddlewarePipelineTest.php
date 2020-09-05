<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Runner;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Runner\Exception\EmptyMiddlewarePipelineException;
use HttpSoft\Runner\MiddlewarePipeline;
use HttpSoft\Tests\Runner\TestAsset\FirstMiddleware;
use HttpSoft\Tests\Runner\TestAsset\PathMiddleware;
use HttpSoft\Tests\Runner\TestAsset\RequestHandler;
use HttpSoft\Tests\Runner\TestAsset\SecondMiddleware;
use HttpSoft\Tests\Runner\TestAsset\ThirdMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewarePipelineTest extends TestCase
{
    /**
     * @var MiddlewarePipeline
     */
    private MiddlewarePipeline $pipeline;

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
        $this->pipeline = new MiddlewarePipeline();
        $this->handler = new RequestHandler();
        $this->request = new ServerRequest();
    }

    public function testHandleWithoutMiddlewareAlwaysThrowsAnException(): void
    {
        $this->expectException(EmptyMiddlewarePipelineException::class);
        $this->pipeline->handle($this->request);
    }

    public function testHandleWithMiddlewareAlwaysThrowsAnException(): void
    {
        $this->pipeline->pipe(new FirstMiddleware());
        $this->pipeline->pipe(new SecondMiddleware());
        $this->pipeline->pipe(new ThirdMiddleware());

        $this->expectException(EmptyMiddlewarePipelineException::class);
        $this->pipeline->handle($this->request);
    }

    public function testHandleWithMiddlewareThatReplacesRequestHandler(): void
    {
        $this->pipeline->pipe(new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return (new RequestHandler())->handle($request);
            }
        });

        $response = $this->pipeline->handle($this->request);
        $this->assertTrue($response->hasHeader('X-Request-Handler'));
    }

    public function testProcessMiddlewareOrderByAsc(): void
    {
        $this->pipeline->pipe(new FirstMiddleware());
        $this->pipeline->pipe(new SecondMiddleware());
        $this->pipeline->pipe(new ThirdMiddleware());

        $response = $this->pipeline->process($this->request, $this->handler);
        $this->assertTrue($response->hasHeader('X-Request-Handler'));
        $this->assertSame(['Third', 'Second', 'First'], $response->getHeader('X-Middleware'));
    }

    public function testProcessMiddlewareOrderByDesc(): void
    {
        $this->pipeline->pipe(new ThirdMiddleware());
        $this->pipeline->pipe(new SecondMiddleware());
        $this->pipeline->pipe(new FirstMiddleware());

        $response = $this->pipeline->process($this->request, $this->handler);
        $this->assertTrue($response->hasHeader('X-Request-Handler'));
        $this->assertSame(['First', 'Second', 'Third'], $response->getHeader('X-Middleware'));
    }

    /**
     * @return array
     */
    public function matchesPathPrefixProvider(): array
    {
        return [
            'empty-not-slash' => ['', 'foo'],
            'empty-leading-slash' => ['', '/foo'],
            'empty-both-slashes' => ['', '/foo/'],
            'empty-trailing-slash' => ['', 'foo/'],
            'empty-nested-path' => ['', '/foo/bar'],
            'slash-not-slash' => ['/', 'foo'],
            'slash-leading-slash' => ['/', '/foo'],
            'slash-both-slashes' => ['/', '/foo/'],
            'slash-trailing-slash' => ['/', 'foo/'],
            'slash-nested-path' => ['/', '/foo/bar'],
            'path-not-slash' => ['/foo', 'foo'],
            'path-leading-slash' => ['foo', '/foo'],
            'path-both-slashes' => ['foo/', '/foo/'],
            'path-trailing-slash' => ['/foo/', 'foo/'],
            'path-one-nested-path' => ['foo', '/foo/bar'],
            'path-two-nested-path' => ['foo', '/foo/bar/baz/'],
            'path-nested-path-file' => ['foo', '/foo/bar/file.txt'],
            'nested-path-one-nested-path' => ['foo/bar/', '/foo/bar'],
            'nested-path-two-nested-path' => ['foo/bar', '/foo/bar/baz/'],
        ];
    }

    /**
     * @dataProvider matchesPathPrefixProvider
     * @param string $pathPrefix
     * @param string $requestUriPath
     */
    public function testPipeMiddlewareMatchesPathPrefix(string $pathPrefix, string $requestUriPath): void
    {
        $this->pipeline->pipe(new PathMiddleware(), $pathPrefix);
        $request = $this->request->withUri($this->request->getUri()->withPath($requestUriPath));
        $response = $this->pipeline->process($request, $this->handler);
        $this->assertTrue($response->hasHeader('X-Path-Prefix'));
        $this->assertTrue($response->hasHeader('X-Request-Handler'));
    }

    /**
     * @return array
     */
    public function notMatchesPathPrefixProvider(): array
    {
        return [
            'not-equal-not-slash' => ['foo', 'bar'],
            'not-equal-leading-slash' => ['/foo', '/bar'],
            'not-equal-both-slashes' => ['/foo/', '/bar/'],
            'not-equal-trailing-slash' => ['foo/', 'bar/'],
            'not-equal-path-boundaries' => ['/foo', '/foobar'],
            'not-equal-nested' => ['/foo/bar', '/foo/baz'],
            'not-equal-one-nested' => ['/foo/bar/', '/foo'],
            'not-equal-two-nested' => ['/foo/bar/baz', '/foo/bar/'],
        ];
    }

    /**
     * @dataProvider notMatchesPathPrefixProvider
     * @param string $pathPrefix
     * @param string $requestUriPath
     */
    public function testPipeMiddlewareNotMatchesPathPrefix(string $pathPrefix, string $requestUriPath): void
    {
        $this->pipeline->pipe(new PathMiddleware(), $pathPrefix);
        $request = $this->request->withUri($this->request->getUri()->withPath($requestUriPath));
        $response = $this->pipeline->process($request, $this->handler);
        $this->assertFalse($response->hasHeader('X-Path-Prefix'));
        $this->assertTrue($response->hasHeader('X-Request-Handler'));
    }
}
