<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Plugin;

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

    public static function createExpirationPlugin(null|int $maxEntries, null|string|int $maxAgeSeconds): static
    {
        return new ExpirationPlugin(
            'ExpirationPlugin',
            [
                'maxEntries' => $maxEntries,
                'maxAgeSeconds' => $maxAgeSeconds,
            ]
        );
    }

    public static function createBroadcastUpdatePlugin(array $headersToCheck = []): static
    {
        $headersToCheck = $headersToCheck === [] ? ['Content-Type', 'ETag', 'Last-Modified'] : $headersToCheck;

        return new BroadcastUpdatePlugin(
            'BroadcastUpdatePlugin',
            [
                'headersToCheck' => $headersToCheck,
            ]
        );
    }

    public static function createCacheableResponsePlugin(array $statuses = [0, 200], array $headers = []): static
    {
        $options = array_filter([
            'statuses' => $statuses,
            'headers' => $headers,
        ], fn ($value) => $value !== []);
        $options = $options === [] ? [
            'statuses' => [0, 200],
        ] : $options;

        return new CacheableResponsePlugin('CacheableResponsePlugin', $options);
    }

    public static function createRangeRequestsPlugin(): static
    {
        return new RangeRequestsPlugin('RangeRequestsPlugin');
    }

    public static function createBackgroundSyncPlugin(
        string $queueName,
        int $maxRetentionTime,
        bool $forceSyncFallback,
        ?string $broadcastChannel
    ): static {
        return new BackgroundSyncPlugin(
            'BackgroundSyncPlugin',
            [
                'queueName' => $queueName,
                'maxRetentionTime' => $maxRetentionTime,
                'forceSyncFallback' => $forceSyncFallback,
                'broadcastChannel' => $broadcastChannel,
            ]
        );
    }
}
