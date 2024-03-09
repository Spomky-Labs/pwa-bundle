<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service;

final readonly class CacheStrategy
{
    public const STRATEGY_CACHE_FIRST = 'cacheFirst';

    public const STRATEGY_CACHE_ONLY = 'cacheOnly';

    public const STRATEGY_NETWORK_FIRST = 'networkFirst';

    public const STRATEGY_NETWORK_ONLY = 'networkOnly';

    public const STRATEGY_STALE_WHILE_REVALIDATE = 'staleWhileRevalidate';

    public const STRATEGIES = [
        self::STRATEGY_CACHE_FIRST,
        self::STRATEGY_CACHE_ONLY,
        self::STRATEGY_NETWORK_FIRST,
        self::STRATEGY_NETWORK_ONLY,
        self::STRATEGY_STALE_WHILE_REVALIDATE,
    ];

    public function __construct(
        public string $name,
        public string $strategy,
        public string $urlPattern,
        public bool $enabled,
        public bool $requireWorkbox,
        /**
         * @var array{maxTimeout?: int, maxAge?: int, maxEntries?: int, warmUrls?: string[], plugins?: string[]}
         */
        public array $options = []
    ) {
    }

    /**
     * @param array{maxTimeout?: int, maxAge?: int, maxEntries?: int, warmUrls?: string[], plugins?: string[]} $options
     */
    public static function create(
        string $name,
        string $strategy,
        string $urlPattern,
        bool $enabled,
        bool $requireWorkbox,
        array $options = [],
    ): self {
        return new self($name, $strategy, $urlPattern, $enabled, $requireWorkbox, $options);
    }
}
