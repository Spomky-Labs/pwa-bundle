<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Plugin;

final readonly class RangeRequestsPlugin extends CachePlugin
{
    public function render(int $jsonOptions = 0): string
    {
        return 'new workbox.rangeRequests.RangeRequestsPlugin()';
    }

    public static function create(): static
    {
        return new self('RangeRequestsPlugin');
    }
}
