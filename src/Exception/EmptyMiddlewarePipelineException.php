<?php

declare(strict_types=1);

namespace HttpSoft\Runner\Exception;

use RuntimeException;

use function sprintf;

class EmptyMiddlewarePipelineException extends RuntimeException
{
    /**
     * @param string $className
     * @return self
     */
    public static function create(string $className): self
    {
        return new self(sprintf(
            '`%s` cannot handle request; there is no middleware in the pipeline to process the request.',
            $className
        ));
    }
}
