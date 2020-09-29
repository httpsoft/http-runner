<?php

declare(strict_types=1);

namespace HttpSoft\Runner\Exception;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function gettype;
use function get_class;
use function is_object;
use function is_scalar;
use function sprintf;

class InvalidMiddlewareResolverHandlerException extends InvalidArgumentException
{
    /**
     * @param mixed $handler
     * @return self
     */
    public static function create($handler): self
    {
        return new self(sprintf(
            'Handler "%s" must be an instance or name of a class that implements `%s` or `%s`'
            . ' interface as well as a PHP callable or array of such arguments.',
            self::convertToString($handler),
            MiddlewareInterface::class,
            RequestHandlerInterface::class
        ));
    }

    /**
     * @param mixed $response
     * @return self
     */
    public static function forCallableMissingResponse($response): self
    {
        return new self(sprintf(
            'Callable handler must returned an instance of `Psr\Http\Message\ResponseInterface`; received "%s".',
            self::convertToString($response)
        ));
    }

    /**
     * @param string $handler
     * @return self
     */
    public static function forStringNotConvertedToInstance(string $handler): self
    {
        return new self(sprintf(
            'String handler "%s" must be a name of a class or an identifier of'
            . ' a container definition that implements `%s` or `%s` interface.',
            $handler,
            MiddlewareInterface::class,
            RequestHandlerInterface::class
        ));
    }

    /**
     * @param mixed $data
     * @return string
     */
    private static function convertToString($data): string
    {
        return is_scalar($data) ? (string) $data : (is_object($data) ? get_class($data) : gettype($data));
    }
}
