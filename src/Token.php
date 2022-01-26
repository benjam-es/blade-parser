<?php

namespace RyanChandler\BladeParser;

use Stringable;

/**
 * @property-read array{int, int} $span
 */
class Token implements Stringable
{
    public function __construct(
        public readonly TokenType $type,
        public readonly string $slice,
        public readonly int $line,
    ) {
    }

    public function __toString(): string
    {
        return $this->slice;
    }
}
