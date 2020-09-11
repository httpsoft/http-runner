<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Runner;

use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Runner\Exception\EmptyMiddlewarePipelineException;
use HttpSoft\Runner\MiddlewarePipeline;
use HttpSoft\Runner\ServerRequestRunner;
use HttpSoft\Tests\Runner\TestAsset\FirstMiddleware;
use HttpSoft\Tests\Runner\TestAsset\RequestHandler;
use HttpSoft\Tests\Runner\TestAsset\SecondMiddleware;
use HttpSoft\Tests\Runner\TestAsset\ThirdMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ServerRequestRunnerTest extends TestCase
{
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
        $this->handler = new RequestHandler();
        $this->request = new ServerRequest();
    }

    public function testRunWithoutRequestHandlerAndWithEmptyMiddlewarePipelineAlwaysThrowsAnException(): void
    {
        $emitter = $this->createEmitter();
        $runner = new ServerRequestRunner(null, $emitter);
        $this->expectException(EmptyMiddlewarePipelineException::class);
        $runner->run($this->request);
    }

    public function testRunWithRequestHandlerAndWithEmptyMiddlewarePipeline(): void
    {
        $emitter = $this->createEmitter();
        $runner = new ServerRequestRunner(null, $emitter);
        $runner->run($this->request, $this->handler);
        $this->expectOutputString('Request Handler Content');
        $this->assertSame(['X-Request-Handler' => ['true']], $emitter->getHeaders());
    }

    public function testRunWithRequestHandlerAndWithMiddlewarePipeline(): void
    {
        $pipeline = new MiddlewarePipeline();
        $pipeline->pipe(new FirstMiddleware());
        $pipeline->pipe(new SecondMiddleware());
        $pipeline->pipe(new ThirdMiddleware());

        $emitter = $this->createEmitter();
        $runner = new ServerRequestRunner($pipeline, $emitter);
        $runner->run($this->request, $this->handler);
        $this->expectOutputString('Request Handler Content');
        $this->assertSame(
            ['X-Request-Handler' => ['true'], 'X-Middleware' => ['Third', 'Second', 'First']],
            $emitter->getHeaders()
        );
    }

    public function testRunWithRequestHandlerAndWithoutResponseBody(): void
    {
        $emitter = $this->createEmitter(true);
        $runner = new ServerRequestRunner(null, $emitter);
        $runner->run($this->request, $this->handler);
        $this->expectOutputString('');
        $this->assertSame(['X-Request-Handler' => ['true']], $emitter->getHeaders());
    }

    private function createEmitter(bool $withoutBody = false): EmitterInterface
    {
        return new class ($withoutBody) implements EmitterInterface {
            private array $headers = [];
            private bool $withoutBody;

            public function __construct(bool $withoutBody = false)
            {
                $this->withoutBody = $withoutBody;
            }

            public function emit(ResponseInterface $response, bool $withoutBody = false): void
            {
                $this->headers = $response->getHeaders();
                echo ($withoutBody || $this->withoutBody) ? '' : $response->getBody();
            }

            public function getHeaders(): array
            {
                return $this->headers;
            }
        };
    }
}
