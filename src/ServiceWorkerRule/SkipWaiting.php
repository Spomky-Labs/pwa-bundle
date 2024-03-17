<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\ServiceWorkerRule;

use SpomkyLabs\PwaBundle\Dto\ServiceWorker;
use const PHP_EOL;

final readonly class SkipWaiting implements ServiceWorkerRule
{
    public function __construct(
        private ServiceWorker $serviceWorker
    ) {
    }

    public function process(string $body): string
    {
        if ($this->serviceWorker->skipWaiting === false) {
            return $body;
        }

        $declaration = <<<SKIP_WAITING
self.addEventListener("install", function (event) {
  event.waitUntil(self.skipWaiting());
});
self.addEventListener("activate", function (event) {
  event.waitUntil(self.clients.claim());
});
SKIP_WAITING;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
