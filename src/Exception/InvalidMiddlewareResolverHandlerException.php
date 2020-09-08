<?php

declare(strict_types=1);

namespace HttpSoft\Runner\Exception;

use InvalidArgumentException;

use function gettype;
use function get_class;
use function is_object;
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
            '`%s` is not a valid handler.',
            (is_object($handler) ? get_class($handler) : gettype($handler))
        ));
    }

    /**
     * @param mixed $response
     * @return self
     */
    public static function forCallableMissingResponse($response): self
    {
        return new self(sprintf(
            'Callable middleware must returned an instance of `Psr\Http\Message\ResponseInterface`; received `%s`.',
            (is_object($response) ? get_class($response) : gettype($response))
        ));
    }
}
