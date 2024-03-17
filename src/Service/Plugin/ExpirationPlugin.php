<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Plugin;

final readonly class ExpirationPlugin extends CachePlugin
{
    public function render(int $jsonOptions = 0): string
    {
        return sprintf('new workbox.expiration.ExpirationPlugin(%s)', json_encode($this->options, $jsonOptions));
    }
}