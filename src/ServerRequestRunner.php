<?php

declare(strict_types=1);

namespace HttpSoft\Runner;

use HttpSoft\Emitter\EmitterInterface;
use HttpSoft\Emitter\SapiEmitter;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function in_array;
use function strtoupper;

final class ServerRequestRunner
{
    /**
     * Response status codes for which the response body is not sent.
     */
    private const NO_BODY_RESPONSE_CODES = [100, 101, 102, 204, 205, 304];

    /**
     * @var MiddlewarePipelineInterface
     */
    private MiddlewarePipelineInterface $pipeline;

    /**
     * @var EmitterInterface
     */
    private EmitterInterface $emitter;

    /**
     * @param MiddlewarePipelineInterface|null $pipeline
     * @param EmitterInterface|null $emitter
     */
    public function __construct(MiddlewarePipelineInterface $pipeline = null, EmitterInterface $emitter = null)
    {
        $this->pipeline = $pipeline ?? new MiddlewarePipeline();
        $this->emitter = $emitter ?? new SapiEmitter();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface|null $defaultHandler
     */
    public function run(ServerRequestInterface $request, RequestHandlerInterface $defaultHandler = null): void
    {
        $response = ($defaultHandler === null)
            ? $this->pipeline->handle($request)
            : $this->pipeline->process($request, $defaultHandler)
        ;

        $this->emitter->emit($response, $this->isResponseWithoutBody(
            (string) $request->getMethod(),
            (int) $response->getStatusCode(),
        ));
    }

    /**
     * @param string $requestMethod
     * @param int $responseCode
     * @return bool
     */
    private function isResponseWithoutBody(string $requestMethod, int $responseCode): bool
    {
        return (strtoupper($requestMethod) === 'HEAD' || in_array($responseCode, self::NO_BODY_RESPONSE_CODES, true));
    }
}
