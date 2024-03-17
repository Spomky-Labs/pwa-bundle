<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

abstract readonly class CachePlugin
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $name,
        public array $options = []
    ) {
    }

    abstract public function render(int $jsonOptions = 0): string;
}
