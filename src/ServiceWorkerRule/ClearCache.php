<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use SpomkyLabs\PwaBundle\Dto\Workbox;

final readonly class ClearCache implements ServiceWorkerRule
{
    private Workbox $workbox;

    public function __construct(
        ServiceWorker $serviceWorker
    ) {
        $this->workbox = $serviceWorker->workbox;
    }

    public function process(): string
    {
        if ($this->workbox->enabled === false) {
            return '';
        }
        if ($this->workbox->clearCache === false) {
            return '';
        }

        $declaration = <<<CLEAR_CACHE
self.addEventListener("install", function (event) {
    event.waitUntil(caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
});
CLEAR_CACHE;

        return trim($declaration);
    }
}
