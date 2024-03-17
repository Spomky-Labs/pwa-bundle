<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Plugin;

final readonly class CacheableResponsePlugin extends CachePlugin
{
    public function render(int $jsonOptions = 0): string
    {
        return sprintf(
            'new workbox.cacheableResponse.CacheableResponsePlugin(%s)',
            json_encode($this->options, $jsonOptions)
        );
    }
}
