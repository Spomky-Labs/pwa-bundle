<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Plugin;

final readonly class BroadcastUpdatePlugin extends CachePlugin
{
    public function render(int $jsonOptions = 0): string
    {
        return sprintf(
            'new workbox.broadcastUpdate.BroadcastUpdatePlugin(%s)',
            json_encode($this->options, $jsonOptions)
        );
    }
}
