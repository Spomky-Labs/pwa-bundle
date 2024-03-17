<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\CachingStrategy;

abstract readonly class CacheStrategy
{
    public const STRATEGY_CACHE_FIRST = 'CacheFirst';

    public const STRATEGY_CACHE_ONLY = 'CacheOnly';

    public const STRATEGY_NETWORK_FIRST = 'NetworkFirst';

    public const STRATEGY_NETWORK_ONLY = 'NetworkOnly';

    public const STRATEGY_STALE_WHILE_REVALIDATE = 'StaleWhileRevalidate';

    public const STRATEGIES = [
        self::STRATEGY_CACHE_FIRST,
        self::STRATEGY_CACHE_ONLY,
        self::STRATEGY_NETWORK_FIRST,
        self::STRATEGY_NETWORK_ONLY,
        self::STRATEGY_STALE_WHILE_REVALIDATE,
    ];

    public function __construct(
        public string $name,
        public bool $enabled,
        public bool $requireWorkbox,
    ) {
    }

    abstract public function render(string $cacheObjectName, int $jsonOptions = 0): string;
}
