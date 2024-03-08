<?php

declare(strict_types=1);

namespace SpomkyLabs\PwaBundle\Service\Rule;

use const PHP_EOL;

final readonly class PrefetchOnDemand implements ServiceWorkerRule
{
    public function process(string $body): string
    {
        $declaration = <<<PREFETCH_STRATEGY
fetchAsync = async (url) => {
  await fetch(url);
}
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'PREFETCH') {
    const urls = event.data.payload.urls || [];
    urls.forEach((url) => fetchAsync(url));
  }
});
PREFETCH_STRATEGY;

        return $body . PHP_EOL . PHP_EOL . trim($declaration);
    }
}
