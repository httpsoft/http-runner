<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Runner\TestAsset;

use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response(200, ['X-Request-Handler' => 'true']);
        $response->getBody()->write('Request Handler Content');
        return $response;
    }
}
