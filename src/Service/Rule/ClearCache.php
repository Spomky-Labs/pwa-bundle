<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use SpomkyLabs\PwaBundle\Dto\Workbox;
use const PHP_EOL;

final readonly class ClearCache implements WorkboxRule
{
    public function process(Workbox $workbox, string $body): string
    {
        if ($workbox->clearCache === false) {
            return $body;
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

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
