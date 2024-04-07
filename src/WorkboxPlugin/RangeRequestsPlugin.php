<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\WorkboxPlugin;

final readonly class RangeRequestsPlugin implements CachePluginInterface, HasDebugInterface
{
    private const NAME = 'RangeRequestsPlugin';

    public function render(int $jsonOptions = 0): string
    {
        return 'new workbox.rangeRequests.RangeRequestsPlugin()';
    }

    public static function create(): self
    {
        return new self();
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDebug(): array
    {
        return [];
    }
}
