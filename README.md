# HTTP Runner

[![License](https://poser.pugx.org/httpsoft/http-runner/license)](https://packagist.org/packages/httpsoft/http-runner)
[![Latest Stable Version](https://poser.pugx.org/httpsoft/http-runner/v)](https://packagist.org/packages/httpsoft/http-runner)
[![Total Downloads](https://poser.pugx.org/httpsoft/http-runner/downloads)](https://packagist.org/packages/httpsoft/http-runner)
[![GitHub Build Status](https://github.com/httpsoft/http-runner/workflows/build/badge.svg)](https://github.com/httpsoft/http-runner/actions)
[![GitHub Static Analysis Status](https://github.com/httpsoft/http-runner/workflows/static/badge.svg)](https://github.com/httpsoft/http-runner/actions)
[![Scrutinizer Code Coverage](https://scrutinizer-ci.com/g/httpsoft/http-runner/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-runner/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/httpsoft/http-runner/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/httpsoft/http-runner/?branch=master)

This package is responsible for running [PSR-7](https://github.com/php-fig/http-message) components and building [PSR-15](https://github.com/php-fig/http-factory) middleware pipelines.

## Documentation

* [In English language](https://httpsoft.org/docs/runner).
* [In Russian language](https://httpsoft.org/ru/docs/runner).

## Installation

This package requires PHP version 7.4 or later.

```
composer require httpsoft/http-runner
```

## Usage

```php
use HttpSoft\Runner\MiddlewarePipeline;
use HttpSoft\Runner\MiddlewareResolver;
use HttpSoft\Runner\ServerRequestRunner;
use Psr\Http\Message\ResponseInterface;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var Psr\Http\Server\RequestHandlerInterface $handler
 * @var Psr\Http\Server\MiddlewareInterface $siteMiddleware
 * @var Psr\Http\Server\MiddlewareInterface $userMiddleware
 */

// Basic usage

$runner = new ServerRequestRunner();
$runner->run($request, $handler);

// Using `MiddlewarePipeline`

$pipeline = new MiddlewarePipeline();
$pipeline->pipe($siteMiddleware);
$pipeline->pipe($userMiddleware, '/user');

$runner = new ServerRequestRunner($pipeline);
$runner->run($request, $handler);

// Using `MiddlewareResolver`

$resolver = new MiddlewareResolver();
$handlerMiddleware = $resolver->resolve(function (): ResponseInterface {
    $response = new HttpSoft\Message\Response();
    $response->getBody()->write('Hello world!');
    return $response;
});

$pipeline = new MiddlewarePipeline();
$pipeline->pipe($siteMiddleware);
$pipeline->pipe($userMiddleware, '/user');
$pipeline->pipe($handlerMiddleware);

$runner = new ServerRequestRunner($pipeline);
$runner->run($request); // Output result: 'Hello world!'
```
